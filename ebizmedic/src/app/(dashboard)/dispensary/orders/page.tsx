import { Suspense } from 'react';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatCurrency, formatDateTime } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { ActionButton } from '@/components/ui/action-button';
import { ShoppingBag } from 'lucide-react';
import Link from 'next/link';

const STATUS_BADGE: Record<string, 'success' | 'warning' | 'danger' | 'secondary' | 'info'> = {
  PENDING: 'warning',
  CONFIRMED: 'info',
  PROCESSING: 'info',
  PACKED: 'secondary',
  OUT_FOR_DELIVERY: 'info',
  COMPLETED: 'success',
  CANCELLED: 'danger',
  REFUNDED: 'secondary',
};

const NEXT_STATUS: Record<string, string | null> = {
  PENDING: 'CONFIRMED',
  CONFIRMED: 'PROCESSING',
  PROCESSING: 'PACKED',
  PACKED: 'OUT_FOR_DELIVERY',
  OUT_FOR_DELIVERY: 'COMPLETED',
  COMPLETED: null,
  CANCELLED: null,
  REFUNDED: null,
};

const NEXT_LABEL: Record<string, string> = {
  CONFIRMED: 'Confirm',
  PROCESSING: 'Start Processing',
  PACKED: 'Mark Packed',
  OUT_FOR_DELIVERY: 'Out for Delivery',
  COMPLETED: 'Mark Delivered',
};

interface Props {
  searchParams: { status?: string };
}

async function OrderTable({ dispensaryId, status }: { dispensaryId: string; status?: string }) {
  const orders = await prisma.order.findMany({
    where: { dispensaryId, ...(status ? { status } : {}) },
    include: {
      patient: { include: { user: { select: { name: true, phone: true } } } },
      items: { include: { product: { select: { name: true, sku: true } } } },
    },
    orderBy: { createdAt: 'desc' },
    take: 50,
  });

  if (orders.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
        <ShoppingBag className="mx-auto h-10 w-10 text-gray-300" />
        <p className="mt-3 text-sm text-gray-500">No orders found</p>
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
      <table className="min-w-full divide-y divide-gray-200 text-sm">
        <thead className="bg-gray-50">
          <tr>
            {['Order Ref', 'Patient', 'Items', 'Total', 'Placed', 'Status', 'Action'].map((h) => (
              <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                {h}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100">
          {orders.map((order) => {
            const next = NEXT_STATUS[order.status];
            return (
              <tr key={order.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-mono text-xs text-gray-500">
                  #{order.orderRef.slice(-8).toUpperCase()}
                </td>
                <td className="px-4 py-3">
                  <div className="font-medium text-gray-900">{order.patient.user.name}</div>
                  <div className="text-xs text-gray-400">{order.patient.user.phone ?? '—'}</div>
                </td>
                <td className="px-4 py-3 text-gray-500">
                  {order.items.map((i) => (
                    <div key={i.id} className="text-xs">{i.quantity}× {i.product.name}</div>
                  ))}
                </td>
                <td className="px-4 py-3 font-semibold text-gray-900">
                  {formatCurrency(Number(order.total))}
                </td>
                <td className="px-4 py-3 text-gray-500">{formatDateTime(order.createdAt)}</td>
                <td className="px-4 py-3">
                  <Badge variant={STATUS_BADGE[order.status] ?? 'secondary'}>
                    {order.status.replace(/_/g, ' ')}
                  </Badge>
                </td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-2">
                    {next && (
                      <ActionButton
                        url={`/api/marketplace/orders/${order.id}`}
                        method="PATCH"
                        body={{ status: next }}
                        variant="primary"
                        size="sm"
                      >
                        {NEXT_LABEL[next] ?? next}
                      </ActionButton>
                    )}
                    {order.status === 'PENDING' && (
                      <ActionButton
                        url={`/api/marketplace/orders/${order.id}`}
                        method="PATCH"
                        body={{ status: 'CANCELLED' }}
                        confirm="Cancel this order?"
                        variant="danger"
                        size="sm"
                      >
                        Cancel
                      </ActionButton>
                    )}
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

export default async function DispensaryOrdersPage({ searchParams }: Props) {
  const user = await requireAuth(['DISPENSARY_STAFF', 'SUPER_ADMIN']);

  const staffEntry = await prisma.dispensaryStaff.findFirst({
    where: { userId: user.id },
    select: { dispensaryId: true },
  });
  if (!staffEntry) return <p className="p-6 text-gray-500">Dispensary staff record not found.</p>;

  const statuses = ['', 'PENDING', 'CONFIRMED', 'PROCESSING', 'PACKED', 'OUT_FOR_DELIVERY', 'COMPLETED'];
  const active = searchParams.status ?? '';

  return (
    <div>
      <PageHeader title="Orders" description="Process and fulfil customer orders" />
      <div className="mb-4 flex flex-wrap gap-2">
        {statuses.map((s) => (
          <Link
            key={s}
            href={s ? `?status=${s}` : '?'}
            className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${
              active === s ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            {s || 'All'}
          </Link>
        ))}
      </div>
      <Suspense fallback={<div className="py-8 text-center text-sm text-gray-400">Loading…</div>}>
        <OrderTable dispensaryId={staffEntry.dispensaryId} status={active || undefined} />
      </Suspense>
    </div>
  );
}
