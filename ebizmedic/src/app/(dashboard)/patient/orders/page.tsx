import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatCurrency, formatDate } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
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

export default async function PatientOrdersPage() {
  const user = await requireAuth(['PATIENT', 'SUPER_ADMIN']);
  const patient = await prisma.patientProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
  if (!patient) return <p className="p-6 text-gray-500">Patient profile not found.</p>;

  const orders = await prisma.order.findMany({
    where: { patientId: patient.id },
    include: {
      dispensary: { select: { name: true } },
      items: { include: { product: { select: { name: true } } } },
    },
    orderBy: { createdAt: 'desc' },
    take: 50,
  });

  return (
    <div>
      <PageHeader
        title="My Orders"
        description="Track your marketplace orders"
        action={
          <Link
            href="/patient/marketplace"
            className="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
          >
            <ShoppingBag className="h-4 w-4" /> Shop
          </Link>
        }
      />

      {orders.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <ShoppingBag className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No orders yet</p>
          <Link
            href="/patient/marketplace"
            className="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
          >
            Browse Marketplace
          </Link>
        </div>
      ) : (
        <div className="space-y-3">
          {orders.map((order) => (
            <div key={order.id} className="rounded-xl border border-gray-200 bg-white p-4">
              <div className="flex items-start justify-between gap-3">
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-xs text-gray-400">#{order.orderRef.slice(-8).toUpperCase()}</span>
                    <Badge variant={STATUS_BADGE[order.status] ?? 'secondary'}>{order.status.replace(/_/g, ' ')}</Badge>
                  </div>
                  <p className="mt-1 text-sm font-medium text-gray-700">{order.dispensary.name}</p>
                  <ul className="mt-1 space-y-0.5">
                    {order.items.map((item) => (
                      <li key={item.id} className="text-xs text-gray-500">
                        {item.quantity}× {item.product.name}
                      </li>
                    ))}
                  </ul>
                  <p className="mt-1 text-xs text-gray-400">{formatDate(order.createdAt)}</p>
                </div>
                <div className="text-right">
                  <p className="text-lg font-bold text-gray-900">{formatCurrency(Number(order.total))}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
