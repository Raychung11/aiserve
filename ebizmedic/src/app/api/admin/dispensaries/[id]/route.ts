import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function GET(_req: NextRequest, { params }: { params: { id: string } }) {
  await requireAuth(['SUPER_ADMIN']);

  const dispensary = await prisma.dispensary.findUnique({
    where: { id: params.id },
    include: {
      staff: { include: { user: { select: { name: true, email: true } } } },
      _count: { select: { products: true, orders: true } },
    },
  });
  if (!dispensary) return fail('Dispensary not found', 404);

  return ok(dispensary);
}

export async function PATCH(req: NextRequest, { params }: { params: { id: string } }) {
  await requireAuth(['SUPER_ADMIN']);

  const body = await req.json();
  const { isActive, name, address, phone, email } = body;

  const dispensary = await prisma.dispensary.findUnique({ where: { id: params.id } });
  if (!dispensary) return fail('Dispensary not found', 404);

  const updated = await prisma.dispensary.update({
    where: { id: params.id },
    data: {
      ...(isActive !== undefined && { isActive }),
      ...(name && { name }),
      ...(address !== undefined && { address }),
      ...(phone !== undefined && { phone }),
      ...(email !== undefined && { email }),
    },
  });

  return ok(updated);
}

export async function DELETE(_req: NextRequest, { params }: { params: { id: string } }) {
  await requireAuth(['SUPER_ADMIN']);

  const dispensary = await prisma.dispensary.findUnique({ where: { id: params.id } });
  if (!dispensary) return fail('Dispensary not found', 404);

  await prisma.dispensary.update({ where: { id: params.id }, data: { deletedAt: new Date() } });

  return ok({ message: 'Dispensary deleted' });
}
