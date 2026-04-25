import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';
import { auditLog } from '@/services/audit.service';

export async function GET(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);

    const org = await prisma.organisation.findUnique({
      where: { id: params.id, deletedAt: null },
      include: {
        adminUser: { select: { id: true, name: true, email: true, phone: true } },
        wallet: { select: { id: true, balance: true, currency: true, lowBalanceThreshold: true } },
        _count: { select: { staff: true } },
      },
    });

    if (!org) return fail('Organisation not found', 404);

    // ORG_ADMIN can only see their own org
    if (user.role === 'ORG_ADMIN' && org.adminUserId !== user.id) {
      return fail('Forbidden', 403);
    }

    return ok(org);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch organisation', 500);
  }
}

export async function PATCH(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);
    const body = await req.json();

    const before = await prisma.organisation.findUnique({ where: { id: params.id } });
    if (!before) return fail('Organisation not found', 404);

    if (user.role === 'ORG_ADMIN' && before.adminUserId !== user.id) {
      return fail('Forbidden', 403);
    }

    const { name, registrationNo, industry, address, city, state, phone, email, logoUrl } = body as Record<string, string>;

    const updated = await prisma.organisation.update({
      where: { id: params.id },
      data: { name, registrationNo, industry, address, city, state, phone, email, logoUrl },
    });

    await auditLog({
      userId: user.id,
      action: 'UPDATE',
      resource: 'Organisation',
      resourceId: params.id,
      oldValue: before,
      newValue: updated,
    });

    return ok(updated);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to update organisation', 500);
  }
}

export async function DELETE(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN']);

    await prisma.organisation.update({
      where: { id: params.id },
      data: { deletedAt: new Date(), isActive: false },
    });

    await auditLog({ userId: user.id, action: 'DELETE', resource: 'Organisation', resourceId: params.id });

    return ok({ deleted: true });
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to delete organisation', 500);
  }
}
