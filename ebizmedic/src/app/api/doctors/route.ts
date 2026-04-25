import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function GET(req: NextRequest) {
  try {
    await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN', 'PATIENT', 'DOCTOR']);
    const { searchParams } = new URL(req.url);
    const page = parseInt(searchParams.get('page') ?? '1');
    const pageSize = parseInt(searchParams.get('pageSize') ?? '20');
    const search = searchParams.get('search') ?? '';
    const specialty = searchParams.get('specialty') ?? '';
    const type = searchParams.get('type') ?? ''; // online | onsite

    const where: Record<string, unknown> = { isVerified: true };
    if (type === 'online') where.isAvailableOnline = true;
    if (type === 'onsite') where.isAvailableOnsite = true;
    if (specialty) where.specialties = { some: { specialty: { contains: specialty, mode: 'insensitive' } } };

    const userWhere = search
      ? { name: { contains: search, mode: 'insensitive' as const }, isActive: true }
      : { isActive: true };

    const [doctors, total] = await Promise.all([
      prisma.doctorProfile.findMany({
        where: { ...where, user: userWhere },
        include: {
          user: { select: { id: true, name: true, email: true, avatarUrl: true } },
          specialties: { where: { isPrimary: true }, take: 1 },
          _count: { select: { appointments: true } },
        },
        orderBy: { rating: 'desc' },
        skip: (page - 1) * pageSize,
        take: pageSize,
      }),
      prisma.doctorProfile.count({ where: { ...where, user: userWhere } }),
    ]);

    return ok({ data: doctors, total, page, pageSize, totalPages: Math.ceil(total / pageSize) });
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch doctors', 500);
  }
}
