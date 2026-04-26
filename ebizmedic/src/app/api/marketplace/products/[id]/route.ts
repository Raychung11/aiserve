import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function GET(_req: NextRequest, { params }: { params: { id: string } }) {
  try {
    await requireAuth();
    const product = await prisma.product.findUnique({
      where: { id: params.id },
      include: { category: true, dispensary: { select: { id: true, name: true } } },
    });
    if (!product) return fail('Product not found', 404);
    return ok(product);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch product', 500);
  }
}

export async function PATCH(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    await requireAuth(['SUPER_ADMIN', 'DISPENSARY_STAFF']);
    const body = await req.json() as {
      name?: string;
      description?: string;
      price?: number;
      stockQuantity?: number;
      isActive?: boolean;
    };

    const allowed = ['name', 'description', 'price', 'stockQuantity', 'isActive'];
    const data: Record<string, unknown> = {};
    for (const key of allowed) {
      if (key in body) data[key] = (body as Record<string, unknown>)[key];
    }

    const updated = await prisma.product.update({ where: { id: params.id }, data });
    return ok(updated);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to update product', 500);
  }
}

export async function DELETE(_req: NextRequest, { params }: { params: { id: string } }) {
  try {
    await requireAuth(['SUPER_ADMIN', 'DISPENSARY_STAFF']);
    await prisma.product.update({
      where: { id: params.id },
      data: { isActive: false, deletedAt: new Date() },
    });
    return ok({ deleted: true });
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to delete product', 500);
  }
}
