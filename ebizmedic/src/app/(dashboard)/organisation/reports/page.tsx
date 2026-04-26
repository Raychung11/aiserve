import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatCurrency } from '@/lib/utils';
import { PageHeader } from '@/components/ui/page-header';
import { BarChart3 } from 'lucide-react';

export default async function OrgReportsPage() {
  const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);

  const org = await prisma.organisation.findFirst({
    where: user.role === 'ORG_ADMIN' ? { adminUserId: user.id } : {},
    include: { wallet: { select: { balance: true, currency: true } } },
  });
  if (!org) return <p className="p-6 text-gray-500">Organisation not found.</p>;

  const staffUserIds = (await prisma.organisationStaff.findMany({
    where: { organisationId: org.id },
    select: { userId: true },
  })).map((s) => s.userId);

  const [totalBookings, completedBookings, totalOrders, walletTxns] = await Promise.all([
    prisma.appointment.count({ where: { patient: { userId: { in: staffUserIds } } } }),
    prisma.appointment.count({ where: { patient: { userId: { in: staffUserIds } }, status: 'COMPLETED' } }),
    prisma.order.count({ where: { patient: { userId: { in: staffUserIds } } } }),
    prisma.walletTransaction.findMany({
      where: { wallet: { organisationId: org.id } },
      orderBy: { createdAt: 'desc' },
      take: 10,
      select: { type: true, amount: true, serviceCategory: true, description: true, createdAt: true },
    }),
  ]);

  const totalSpend = walletTxns
    .filter((t) => t.type === 'DEDUCTION')
    .reduce((s, t) => s + Number(t.amount), 0);

  const cards = [
    { label: 'Wallet Balance', value: formatCurrency(Number(org.wallet?.balance ?? 0), org.wallet?.currency ?? 'MYR'), colour: 'bg-emerald-50 text-emerald-700' },
    { label: 'Total Bookings', value: String(totalBookings), colour: 'bg-blue-50 text-blue-700' },
    { label: 'Completed', value: String(completedBookings), colour: 'bg-purple-50 text-purple-700' },
    { label: 'Marketplace Orders', value: String(totalOrders), colour: 'bg-amber-50 text-amber-700' },
  ];

  return (
    <div>
      <PageHeader title="Reports" description={`Usage summary for ${org.name}`} />

      <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        {cards.map((c) => (
          <div key={c.label} className={`rounded-xl p-4 ${c.colour}`}>
            <p className="text-xs font-medium opacity-70">{c.label}</p>
            <p className="mt-1 text-2xl font-bold">{c.value}</p>
          </div>
        ))}
      </div>

      <div className="rounded-xl border border-gray-200 bg-white">
        <div className="border-b px-4 py-3">
          <h2 className="text-sm font-semibold text-gray-900">Recent Wallet Activity</h2>
        </div>
        {walletTxns.length === 0 ? (
          <div className="py-8 text-center">
            <BarChart3 className="mx-auto h-8 w-8 text-gray-300" />
            <p className="mt-2 text-sm text-gray-500">No transactions yet</p>
          </div>
        ) : (
          <table className="min-w-full divide-y divide-gray-100 text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['Type', 'Category', 'Description', 'Amount'].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {walletTxns.map((t, i) => (
                <tr key={i} className="hover:bg-gray-50">
                  <td className={`px-4 py-3 font-medium ${t.type === 'DEDUCTION' ? 'text-red-600' : 'text-emerald-600'}`}>{t.type}</td>
                  <td className="px-4 py-3 text-gray-500">{t.serviceCategory ?? '—'}</td>
                  <td className="px-4 py-3 text-gray-500">{t.description ?? '—'}</td>
                  <td className={`px-4 py-3 font-semibold ${t.type === 'DEDUCTION' ? 'text-red-600' : 'text-emerald-600'}`}>
                    {t.type === 'DEDUCTION' ? '-' : '+'}{formatCurrency(Number(t.amount))}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}
