import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';
import { z } from 'zod';

const createOrderSchema = z.object({
  dispensaryId: z.string(),
  items: z.array(z.object({ productId: z.string(), quantity: z.number().int().positive() })).min(1),
  deliveryAddress: z.string().optional(),
  notes: z.string().optional(),
});

export async function GET(req: NextRequest) {
  try {
    const user = await requireAuth();
    const { searchParams } = new URL(req.url);
    const page = parseInt(searchParams.get('page') ?? '1');
    const pageSize = parseInt(searchParams.get('pageSize') ?? '20');
    const status = searchParams.get('status');

    const where: Record<string, unknown> = {};

    if (user.role === 'PATIENT') {
      const patient = await prisma.patientProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
      if (!patient) return fail('Patient profile not found', 404);
      where.patientId = patient.id;
    } else if (user.role === 'DISPENSARY_STAFF') {
      const staffEntry = await prisma.dispensaryStaff.findFirst({ where: { userId: user.id }, select: { dispensaryId: true } });
      if (!staffEntry) return fail('Dispensary staff record not found', 404);
      where.dispensaryId = staffEntry.dispensaryId;
    }

    if (status) where.status = status;

    const [orders, total] = await Promise.all([
      prisma.order.findMany({
        where,
        include: {
          patient: { include: { user: { select: { name: true, email: true } } } },
          dispensary: { select: { name: true } },
          items: { include: { product: { select: { name: true, sku: true } } } },
        },
        orderBy: { createdAt: 'desc' },
        skip: (page - 1) * pageSize,
        take: pageSize,
      }),
      prisma.order.count({ where }),
    ]);

    return ok({ data: orders, total, page, pageSize, totalPages: Math.ceil(total / pageSize) });
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch orders', 500);
  }
}

export async function POST(req: NextRequest) {
  try {
    const user = await requireAuth(['PATIENT']);
    const patient = await prisma.patientProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
    if (!patient) return fail('Patient profile not found', 404);

    const body = await req.json();
    const data = createOrderSchema.parse(body);

    const productIds = data.items.map((i) => i.productId);
    const products = await prisma.product.findMany({
      where: { id: { in: productIds }, dispensaryId: data.dispensaryId, isActive: true, deletedAt: null },
      select: { id: true, price: true, stockQuantity: true, name: true },
    });

    if (products.length !== productIds.length) return fail('One or more products not found', 404);

    const quantityMap = new Map(data.items.map((i) => [i.productId, i.quantity]));
    for (const p of products) {
      const qty = quantityMap.get(p.id) ?? 0;
      if (p.stockQuantity < qty) return fail(`Insufficient stock for ${p.name}`, 400);
    }

    const priceMap = new Map(products.map((p) => [p.id, Number(p.price)]));
    const subtotal = data.items.reduce((sum, i) => sum + (priceMap.get(i.productId) ?? 0) * i.quantity, 0);
    const total = subtotal;

    const order = await prisma.$transaction(async (tx) => {
      const created = await tx.order.create({
        data: {
          patientId: patient.id,
          dispensaryId: data.dispensaryId,
          subtotal,
          total,
          deliveryAddress: data.deliveryAddress,
          notes: data.notes,
          items: {
            create: data.items.map((i) => ({
              productId: i.productId,
              quantity: i.quantity,
              unitPrice: priceMap.get(i.productId) ?? 0,
              subtotal: (priceMap.get(i.productId) ?? 0) * i.quantity,
            })),
          },
        },
        include: { items: true },
      });

      for (const i of data.items) {
        await tx.product.update({
          where: { id: i.productId },
          data: { stockQuantity: { decrement: i.quantity } },
        });
      }

      return created;
    });

    return ok(order, 201);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    if (err instanceof z.ZodError) return fail('Invalid order data', 400);
    if (err instanceof Error) return fail(err.message, 400);
    return fail('Failed to create order', 500);
  }
}
