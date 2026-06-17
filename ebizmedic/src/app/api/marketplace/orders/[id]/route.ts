import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

const VALID_TRANSITIONS: Record<string, string[]> = {
  PENDING: ['CONFIRMED', 'CANCELLED'],
  CONFIRMED: ['PROCESSING', 'CANCELLED'],
  PROCESSING: ['PACKED'],
  PACKED: ['OUT_FOR_DELIVERY'],
  OUT_FOR_DELIVERY: ['COMPLETED'],
};

export async function GET(_req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth();
    const order = await prisma.order.findUnique({
      where: { id: params.id },
      include: {
        patient: { include: { user: { select: { name: true, email: true, phone: true } } } },
        dispensary: { select: { name: true } },
        items: { include: { product: { select: { name: true, sku: true, imageUrl: true } } } },
        statusLogs: { orderBy: { createdAt: 'asc' } },
      },
    });
    if (!order) return fail('Order not found', 404);

    if (user.role === 'PATIENT' && order.patient.userId !== user.id) return fail('Forbidden', 403);
    return ok(order);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch order', 500);
  }
}

export async function PATCH(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN', 'DISPENSARY_STAFF', 'PATIENT']);
    const body = await req.json() as { status: string; note?: string };

    const order = await prisma.order.findUnique({ where: { id: params.id }, select: { status: true } });
    if (!order) return fail('Order not found', 404);

    const allowed = VALID_TRANSITIONS[order.status] ?? [];
    if (!allowed.includes(body.status)) {
      return fail(`Cannot transition from ${order.status} to ${body.status}`, 400);
    }

    const updated = await prisma.$transaction(async (tx) => {
      const o = await tx.order.update({ where: { id: params.id }, data: { status: body.status as never } });
      await tx.orderStatusLog.create({
        data: { orderId: params.id, status: body.status as never, note: body.note, performedBy: user.id },
      });
      return o;
    });

    return ok(updated);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to update order', 500);
  }
}
