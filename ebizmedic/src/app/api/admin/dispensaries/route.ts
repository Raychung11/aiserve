import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function GET(req: NextRequest) {
  await requireAuth(['SUPER_ADMIN']);

  const { searchParams } = new URL(req.url);
  const search = searchParams.get('search') ?? '';

  const where = search
    ? { deletedAt: null, name: { contains: search, mode: 'insensitive' as const } }
    : { deletedAt: null };

  const dispensaries = await prisma.dispensary.findMany({
    where,
    include: { _count: { select: { products: true, orders: true, staff: true } } },
    orderBy: { createdAt: 'desc' },
    take: 100,
  });

  return ok(dispensaries);
}

export async function POST(req: NextRequest) {
  await requireAuth(['SUPER_ADMIN']);

  const body = await req.json();
  const { name, email, phone, address } = body;

  if (!name) return fail('Name is required', 400);

  const dispensary = await prisma.dispensary.create({
    data: {
      name,
      email: email || undefined,
      phone: phone || undefined,
      address: address || undefined,
    },
  });

  return ok(dispensary);
}
