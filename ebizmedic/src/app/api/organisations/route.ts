import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { createOrganisationSchema } from '@/lib/validations';
import { ok, fail } from '@/lib/utils';
import { auditLog } from '@/services/audit.service';
import bcrypt from 'bcryptjs';

export async function GET(req: NextRequest) {
  try {
    const user = await requireAuth(['SUPER_ADMIN']);
    const { searchParams } = new URL(req.url);
    const page = parseInt(searchParams.get('page') ?? '1');
    const pageSize = parseInt(searchParams.get('pageSize') ?? '20');
    const search = searchParams.get('search') ?? '';

    const where = search
      ? { OR: [{ name: { contains: search, mode: 'insensitive' as const } }, { email: { contains: search, mode: 'insensitive' as const } }], deletedAt: null }
      : { deletedAt: null };

    const [organisations, total] = await Promise.all([
      prisma.organisation.findMany({
        where,
        include: {
          adminUser: { select: { id: true, name: true, email: true } },
          wallet: { select: { balance: true, currency: true } },
          _count: { select: { staff: true } },
        },
        orderBy: { createdAt: 'desc' },
        skip: (page - 1) * pageSize,
        take: pageSize,
      }),
      prisma.organisation.count({ where }),
    ]);

    return ok({ data: organisations, total, page, pageSize, totalPages: Math.ceil(total / pageSize) });
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch organisations', 500);
  }
}

export async function POST(req: NextRequest) {
  try {
    const user = await requireAuth(['SUPER_ADMIN']);
    const body = await req.json();
    const data = createOrganisationSchema.parse(body);

    const { adminEmail, adminName, adminPassword } = body as {
      adminEmail: string;
      adminName: string;
      adminPassword: string;
    };

    if (!adminEmail || !adminName || !adminPassword) {
      return fail('Admin email, name, and password are required', 400);
    }

    const existing = await prisma.user.findUnique({ where: { email: adminEmail } });
    if (existing) return fail('An account with this admin email already exists', 409);

    const org = await prisma.$transaction(async (tx) => {
      const adminUser = await tx.user.create({
        data: {
          email: adminEmail,
          name: adminName,
          passwordHash: await bcrypt.hash(adminPassword, 12),
          role: 'ORG_ADMIN',
        },
      });

      const organisation = await tx.organisation.create({
        data: { ...data, adminUserId: adminUser.id },
      });

      await tx.organisationWallet.create({
        data: { organisationId: organisation.id },
      });

      return organisation;
    });

    await auditLog({
      userId: user.id,
      action: 'CREATE',
      resource: 'Organisation',
      resourceId: org.id,
      newValue: { name: org.name },
    });

    return ok(org, 201);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    if (err instanceof Error && err.name === 'ZodError') return fail('Invalid data', 400);
    console.error('[ORG CREATE]', err);
    return fail('Failed to create organisation', 500);
  }
}
