import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';
import bcrypt from 'bcryptjs';
import { z } from 'zod';

const addStaffSchema = z.object({
  name: z.string().min(2),
  email: z.string().email(),
  password: z.string().min(8),
  phone: z.string().optional(),
  monthlyLimit: z.number().positive().optional(),
  isEligible: z.boolean().optional(),
});

export async function GET(_req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);

    const org = await prisma.organisation.findUnique({ where: { id: params.id }, select: { adminUserId: true } });
    if (!org) return fail('Organisation not found', 404);
    if (user.role === 'ORG_ADMIN' && org.adminUserId !== user.id) return fail('Forbidden', 403);

    const staff = await prisma.organisationStaff.findMany({
      where: { organisationId: params.id, user: { deletedAt: null } },
      include: { user: { select: { id: true, name: true, email: true, phone: true, avatarUrl: true } } },
      orderBy: { joinedAt: 'desc' },
    });

    return ok(staff);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch staff', 500);
  }
}

export async function POST(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);

    const org = await prisma.organisation.findUnique({ where: { id: params.id }, select: { adminUserId: true } });
    if (!org) return fail('Organisation not found', 404);
    if (user.role === 'ORG_ADMIN' && org.adminUserId !== user.id) return fail('Forbidden', 403);

    const body = await req.json();
    const data = addStaffSchema.parse(body);

    const existing = await prisma.user.findUnique({ where: { email: data.email } });
    if (existing) return fail('Email already registered', 409);

    const staffMember = await prisma.$transaction(async (tx) => {
      const newUser = await tx.user.create({
        data: {
          email: data.email,
          name: data.name,
          phone: data.phone,
          passwordHash: await bcrypt.hash(data.password, 12),
          role: 'PATIENT',
        },
      });

      return tx.organisationStaff.create({
        data: {
          organisationId: params.id,
          userId: newUser.id,
          isEligible: data.isEligible ?? true,
          monthlyLimit: data.monthlyLimit,
        },
        include: { user: { select: { id: true, name: true, email: true } } },
      });
    });

    return ok(staffMember, 201);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    if (err instanceof z.ZodError) return fail('Invalid data', 400);
    return fail('Failed to add staff', 500);
  }
}

export async function PATCH(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);

    const org = await prisma.organisation.findUnique({ where: { id: params.id }, select: { adminUserId: true } });
    if (!org) return fail('Organisation not found', 404);
    if (user.role === 'ORG_ADMIN' && org.adminUserId !== user.id) return fail('Forbidden', 403);

    const body = await req.json() as { staffId: string; isEligible?: boolean; monthlyLimit?: number };
    if (!body.staffId) return fail('staffId required', 400);

    const updated = await prisma.organisationStaff.update({
      where: { id: body.staffId, organisationId: params.id },
      data: {
        ...(body.isEligible !== undefined && { isEligible: body.isEligible }),
        ...(body.monthlyLimit !== undefined && { monthlyLimit: body.monthlyLimit }),
      },
    });

    return ok(updated);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to update staff', 500);
  }
}
