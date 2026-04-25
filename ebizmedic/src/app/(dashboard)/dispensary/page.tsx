import { Suspense } from 'react';
import { requireAuth } from '@/lib/auth';
import { prisma } from '@/lib/db';
import { StatsCard } from '@/components/dashboard/stats-card';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { OrderStatusBadge } from '@/components/ui/badge';
import { Package, ShoppingBag, ClipboardList, TrendingUp } from 'lucide-react';
import { formatCurrency, formatDate } from '@/lib/utils';

export const metadata = { title: 'Dispensary Dashboard' };

async function DispensaryDashboardContent() {
  const user = await requireAuth(['DISPENSARY_STAFF', 'SUPER_ADMIN']);

  const dispensaryStaff = await prisma.dispensaryStaff.findFirst({
    where: { userId: user.id },
    include: { dispensary: true },
  });

  if (!dispensaryStaff) {
    return (
      <div className="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center">
        <p className="font-semibold text-amber-700">Not assigned to a dispensary</p>
        <p className="mt-1 text-sm text-amber-600">Contact your platform administrator.</p>
      </div>
    );
  }

  const { dispensary } = dispensaryStaff;

  const [productCount, pendingOrders, activeOrders, monthRevenue] = await Promise.all([
    prisma.product.count({ where: { dispensaryId: dispensary.id, isActive: true } }),
    prisma.order.count({ where: { dispensaryId: dispensary.id, status: 'PENDING' } }),
    prisma.order.count({
      where: { dispensaryId: dispensary.id, status: { in: ['CONFIRMED', 'PROCESSING', 'PACKED', 'OUT_FOR_DELIVERY'] } },
    }),
    prisma.order.aggregate({
      where: {
        dispensaryId: dispensary.id,
        status: 'COMPLETED',
        createdAt: { gte: new Date(new Date().getFullYear(), new Date().getMonth(), 1) },
      },
      _sum: { total: true },
    }),
  ]);

  const recentOrders = await prisma.order.findMany({
    where: { dispensaryId: dispensary.id },
    include: {
      patient: { include: { user: { select: { name: true } } } },
      _count: { select: { items: true } },
    },
    orderBy: { createdAt: 'desc' },
    take: 8,
  });

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">{dispensary.name}</h2>
        <p className="text-sm text-gray-500">Dispensary operations dashboard</p>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          title="Active Products"
          value={productCount.toLocaleString()}
          icon={Package}
          iconBg="bg-blue-50"
          iconColor="text-blue-600"
        />
        <StatsCard
          title="Pending Orders"
          value={pendingOrders}
          icon={ClipboardList}
          iconBg="bg-amber-50"
          iconColor="text-amber-600"
        />
        <StatsCard
          title="Orders in Progress"
          value={activeOrders}
          icon={ShoppingBag}
          iconBg="bg-purple-50"
          iconColor="text-purple-600"
        />
        <StatsCard
          title="Month Revenue"
          value={formatCurrency(Number(monthRevenue._sum.total ?? 0))}
          icon={TrendingUp}
          iconBg="bg-emerald-50"
          iconColor="text-emerald-600"
        />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Recent Orders</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100 text-left text-gray-500">
                  <th className="pb-3 font-medium">Order Ref</th>
                  <th className="pb-3 font-medium">Patient</th>
                  <th className="pb-3 font-medium">Items</th>
                  <th className="pb-3 font-medium">Total</th>
                  <th className="pb-3 font-medium">Date</th>
                  <th className="pb-3 font-medium">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {recentOrders.map((order) => (
                  <tr key={order.id} className="hover:bg-gray-50">
                    <td className="py-3 font-mono text-xs text-gray-600">
                      {order.orderRef.slice(-8).toUpperCase()}
                    </td>
                    <td className="py-3 font-medium text-gray-900">{order.patient.user.name}</td>
                    <td className="py-3 text-gray-600">{order._count.items}</td>
                    <td className="py-3 font-medium text-gray-900">{formatCurrency(Number(order.total))}</td>
                    <td className="py-3 text-gray-600">{formatDate(order.createdAt)}</td>
                    <td className="py-3">
                      <OrderStatusBadge status={order.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {!recentOrders.length && (
              <p className="py-6 text-center text-sm text-gray-500">No orders yet.</p>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

export default async function DispensaryDashboard() {
  return (
    <Suspense fallback={<div className="h-96 animate-pulse rounded-xl bg-gray-100" />}>
      <DispensaryDashboardContent />
    </Suspense>
  );
}
