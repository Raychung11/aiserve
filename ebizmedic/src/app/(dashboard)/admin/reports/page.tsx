import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatCurrency, formatDate } from '@/lib/utils';
import { PageHeader } from '@/components/ui/page-header';
import { BarChart3 } from 'lucide-react';

export default async function AdminReportsPage() {
  await requireAuth(['SUPER_ADMIN']);

  const now = new Date();
  const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
  const startOfLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
  const endOfLastMonth = new Date(now.getFullYear(), now.getMonth(), 0);

  const [
    totalOrgs,
    activeOrgs,
    totalDoctors,
    verifiedDoctors,
    totalPatients,
    totalBookings,
    bookingsThisMonth,
    completedBookings,
    totalOrders,
    ordersThisMonth,
    recentTransactions,
    bookingsByType,
    topDoctors,
  ] = await Promise.all([
    prisma.organisation.count({ where: { deletedAt: null } }),
    prisma.organisation.count({ where: { deletedAt: null, isActive: true } }),
    prisma.doctorProfile.count(),
    prisma.doctorProfile.count({ where: { isVerified: true } }),
    prisma.patientProfile.count(),
    prisma.appointment.count(),
    prisma.appointment.count({ where: { createdAt: { gte: startOfMonth } } }),
    prisma.appointment.count({ where: { status: 'COMPLETED' } }),
    prisma.order.count(),
    prisma.order.count({ where: { createdAt: { gte: startOfMonth } } }),
    prisma.walletTransaction.findMany({
      orderBy: { createdAt: 'desc' },
      take: 10,
      select: { type: true, amount: true, serviceCategory: true, description: true, createdAt: true, wallet: { select: { organisation: { select: { name: true } } } } },
    }),
    prisma.appointment.groupBy({
      by: ['type'],
      _count: { id: true },
      orderBy: { _count: { id: 'desc' } },
    }),
    prisma.doctorProfile.findMany({
      orderBy: { _count: { appointments: 'desc' } },
      take: 5,
      select: {
        user: { select: { name: true } },
        specialties: { where: { isPrimary: true }, take: 1 },
        _count: { select: { appointments: true } },
      },
    }),
  ]);

  const totalWalletBalance = await prisma.organisationWallet.aggregate({ _sum: { balance: true } });
  const totalRevenue = await prisma.walletTransaction.aggregate({
    where: { type: 'DEDUCTION' },
    _sum: { amount: true },
  });

  const platformCards = [
    { label: 'Organisations', value: `${activeOrgs} / ${totalOrgs}`, sub: 'active / total', colour: 'bg-blue-50 text-blue-700' },
    { label: 'Doctors', value: `${verifiedDoctors} / ${totalDoctors}`, sub: 'verified / total', colour: 'bg-purple-50 text-purple-700' },
    { label: 'Patients', value: totalPatients, sub: 'registered', colour: 'bg-emerald-50 text-emerald-700' },
    { label: 'Total Wallet Funds', value: formatCurrency(Number(totalWalletBalance._sum.balance ?? 0)), sub: 'across all orgs', colour: 'bg-amber-50 text-amber-700' },
  ];

  const activityCards = [
    { label: 'All Bookings', value: totalBookings, sub: `${bookingsThisMonth} this month`, colour: 'bg-blue-50 text-blue-700' },
    { label: 'Completed', value: completedBookings, sub: `${Math.round((completedBookings / Math.max(totalBookings, 1)) * 100)}% completion rate`, colour: 'bg-emerald-50 text-emerald-700' },
    { label: 'Marketplace Orders', value: totalOrders, sub: `${ordersThisMonth} this month`, colour: 'bg-purple-50 text-purple-700' },
    { label: 'Platform Revenue', value: formatCurrency(Number(totalRevenue._sum.amount ?? 0)), sub: 'total wallet deductions', colour: 'bg-red-50 text-red-700' },
  ];

  return (
    <div className="space-y-8">
      <PageHeader title="Platform Reports" description="Usage statistics and financial overview" />

      <section>
        <h2 className="mb-3 text-sm font-semibold text-gray-700">Platform Overview</h2>
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          {platformCards.map((c) => (
            <div key={c.label} className={`rounded-xl p-4 ${c.colour}`}>
              <p className="text-xs font-medium opacity-70">{c.label}</p>
              <p className="mt-1 text-2xl font-bold">{c.value}</p>
              <p className="mt-0.5 text-xs opacity-60">{c.sub}</p>
            </div>
          ))}
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-sm font-semibold text-gray-700">Activity</h2>
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          {activityCards.map((c) => (
            <div key={c.label} className={`rounded-xl p-4 ${c.colour}`}>
              <p className="text-xs font-medium opacity-70">{c.label}</p>
              <p className="mt-1 text-2xl font-bold">{c.value}</p>
              <p className="mt-0.5 text-xs opacity-60">{c.sub}</p>
            </div>
          ))}
        </div>
      </section>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section className="rounded-xl border border-gray-200 bg-white">
          <div className="border-b px-4 py-3">
            <h2 className="text-sm font-semibold text-gray-900">Bookings by Type</h2>
          </div>
          <div className="divide-y divide-gray-100">
            {bookingsByType.map((b) => (
              <div key={b.type} className="flex items-center justify-between px-4 py-3">
                <span className="text-sm text-gray-700">{b.type.replace(/_/g, ' ')}</span>
                <span className="text-sm font-semibold text-gray-900">{b._count.id}</span>
              </div>
            ))}
            {bookingsByType.length === 0 && (
              <p className="px-4 py-6 text-center text-sm text-gray-400">No bookings yet</p>
            )}
          </div>
        </section>

        <section className="rounded-xl border border-gray-200 bg-white">
          <div className="border-b px-4 py-3">
            <h2 className="text-sm font-semibold text-gray-900">Top Doctors by Bookings</h2>
          </div>
          <div className="divide-y divide-gray-100">
            {topDoctors.map((doc, i) => (
              <div key={i} className="flex items-center justify-between px-4 py-3">
                <div>
                  <p className="text-sm font-medium text-gray-900">Dr. {doc.user.name}</p>
                  <p className="text-xs text-gray-400">{doc.specialties[0]?.specialty ?? 'General'}</p>
                </div>
                <span className="text-sm font-semibold text-gray-900">{doc._count.appointments} bookings</span>
              </div>
            ))}
            {topDoctors.length === 0 && (
              <p className="px-4 py-6 text-center text-sm text-gray-400">No data yet</p>
            )}
          </div>
        </section>
      </div>

      <section className="rounded-xl border border-gray-200 bg-white">
        <div className="border-b px-4 py-3">
          <h2 className="text-sm font-semibold text-gray-900">Recent Wallet Transactions</h2>
        </div>
        {recentTransactions.length === 0 ? (
          <div className="py-8 text-center">
            <BarChart3 className="mx-auto h-8 w-8 text-gray-300" />
            <p className="mt-2 text-sm text-gray-500">No transactions yet</p>
          </div>
        ) : (
          <table className="min-w-full divide-y divide-gray-100 text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['Organisation', 'Type', 'Category', 'Description', 'Amount', 'Date'].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {recentTransactions.map((t, i) => (
                <tr key={i} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-900">{t.wallet.organisation?.name ?? '—'}</td>
                  <td className={`px-4 py-3 font-medium ${t.type === 'DEDUCTION' ? 'text-red-600' : 'text-emerald-600'}`}>{t.type}</td>
                  <td className="px-4 py-3 text-gray-500">{t.serviceCategory ?? '—'}</td>
                  <td className="px-4 py-3 text-gray-500">{t.description ?? '—'}</td>
                  <td className={`px-4 py-3 font-semibold ${t.type === 'DEDUCTION' ? 'text-red-600' : 'text-emerald-600'}`}>
                    {t.type === 'DEDUCTION' ? '-' : '+'}{formatCurrency(Number(t.amount))}
                  </td>
                  <td className="px-4 py-3 text-gray-500">{formatDate(t.createdAt)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </section>
    </div>
  );
}
