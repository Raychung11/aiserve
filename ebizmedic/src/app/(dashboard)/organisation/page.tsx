import { Suspense } from 'react';
import { requireAuth } from '@/lib/auth';
import { prisma } from '@/lib/db';
import { StatsCard } from '@/components/dashboard/stats-card';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { AppointmentStatusBadge } from '@/components/ui/badge';
import { Users, Wallet, Calendar, TrendingUp } from 'lucide-react';
import { formatCurrency, formatDate } from '@/lib/utils';

export const metadata = { title: 'Organisation Dashboard' };

async function OrgDashboardContent() {
  const user = await requireAuth(['ORG_ADMIN', 'SUPER_ADMIN']);

  const org = await prisma.organisation.findUnique({
    where: { adminUserId: user.id },
    include: {
      wallet: {
        include: {
          transactions: {
            orderBy: { createdAt: 'desc' },
            take: 5,
          },
        },
      },
      _count: { select: { staff: true } },
    },
  });

  if (!org) {
    return (
      <div className="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center">
        <p className="font-semibold text-amber-700">Organisation not set up</p>
        <p className="mt-1 text-sm text-amber-600">Contact your platform administrator.</p>
      </div>
    );
  }

  const activeBookings = await prisma.appointment.count({
    where: {
      status: { in: ['PENDING', 'CONFIRMED'] },
      patient: {
        user: {
          orgStaff: { some: { organisationId: org.id } },
        },
      },
    },
  });

  const monthStart = new Date();
  monthStart.setDate(1);
  monthStart.setHours(0, 0, 0, 0);

  const monthlySpend = await prisma.walletTransaction.aggregate({
    where: {
      walletId: org.wallet?.id,
      type: 'DEDUCTION',
      createdAt: { gte: monthStart },
    },
    _sum: { amount: true },
  });

  const balance = Number(org.wallet?.balance ?? 0);
  const spent = Number(monthlySpend._sum.amount ?? 0);

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">{org.name}</h2>
        <p className="text-sm text-gray-500">Organisation dashboard</p>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          title="Wallet Balance"
          value={formatCurrency(balance)}
          icon={Wallet}
          iconBg="bg-primary-50"
          iconColor="text-primary-600"
        />
        <StatsCard
          title="Staff Members"
          value={org._count.staff}
          icon={Users}
          iconBg="bg-purple-50"
          iconColor="text-purple-600"
        />
        <StatsCard
          title="Active Bookings"
          value={activeBookings}
          icon={Calendar}
          iconBg="bg-amber-50"
          iconColor="text-amber-600"
        />
        <StatsCard
          title="This Month Spend"
          value={formatCurrency(spent)}
          icon={TrendingUp}
          iconBg="bg-rose-50"
          iconColor="text-rose-600"
        />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Wallet Ledger */}
        <Card>
          <CardHeader>
            <CardTitle>Recent Transactions</CardTitle>
          </CardHeader>
          <CardContent>
            {org.wallet?.transactions.length ? (
              <div className="space-y-3">
                {org.wallet.transactions.map((tx) => (
                  <div key={tx.id} className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-gray-900">
                        {tx.type.charAt(0) + tx.type.slice(1).toLowerCase()}
                      </p>
                      <p className="text-xs text-gray-500">{formatDate(tx.createdAt)}</p>
                    </div>
                    <p
                      className={`font-semibold ${
                        tx.type === 'TOPUP' || tx.type === 'REFUND'
                          ? 'text-emerald-600'
                          : 'text-red-600'
                      }`}
                    >
                      {tx.type === 'TOPUP' || tx.type === 'REFUND' ? '+' : '-'}
                      {formatCurrency(Number(tx.amount))}
                    </p>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-gray-500">No transactions yet.</p>
            )}
          </CardContent>
        </Card>

        {/* Staff quick view */}
        <Card>
          <CardHeader>
            <CardTitle>Staff Overview</CardTitle>
          </CardHeader>
          <CardContent>
            <StaffOverview organisationId={org.id} />
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

async function StaffOverview({ organisationId }: { organisationId: string }) {
  const staff = await prisma.organisationStaff.findMany({
    where: { organisationId },
    include: { user: { select: { name: true, email: true, isActive: true } } },
    take: 6,
    orderBy: { joinedAt: 'desc' },
  });

  return (
    <div className="space-y-2">
      {staff.map((s) => (
        <div key={s.id} className="flex items-center gap-3 rounded-lg border border-gray-100 p-2.5">
          <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">
            {s.user.name.slice(0, 2).toUpperCase()}
          </div>
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-medium text-gray-900">{s.user.name}</p>
            <p className="truncate text-xs text-gray-500">{s.department ?? 'No department'}</p>
          </div>
          <span className={`text-xs font-medium ${s.isEligible ? 'text-emerald-600' : 'text-gray-400'}`}>
            {s.isEligible ? 'Active' : 'Inactive'}
          </span>
        </div>
      ))}
      {!staff.length && <p className="text-sm text-gray-500">No staff added yet.</p>}
    </div>
  );
}

export default async function OrganisationDashboard() {
  return (
    <Suspense fallback={<div className="h-96 animate-pulse rounded-xl bg-gray-100" />}>
      <OrgDashboardContent />
    </Suspense>
  );
}
