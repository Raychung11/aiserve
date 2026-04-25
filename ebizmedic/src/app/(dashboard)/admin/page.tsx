import { Suspense } from 'react';
import { requireAuth } from '@/lib/auth';
import { prisma } from '@/lib/db';
import { StatsCard } from '@/components/dashboard/stats-card';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge, AppointmentStatusBadge } from '@/components/ui/badge';
import {
  Building2,
  Stethoscope,
  Users,
  Calendar,
  Wallet,
  ShoppingBag,
} from 'lucide-react';
import { formatCurrency, formatDateTime } from '@/lib/utils';

export const metadata = { title: 'Admin Dashboard' };

async function AdminStats() {
  const [orgCount, doctorCount, patientCount, bookingCount, walletSum, productCount] =
    await Promise.all([
      prisma.organisation.count({ where: { deletedAt: null } }),
      prisma.doctorProfile.count({ where: { isVerified: true } }),
      prisma.patientProfile.count(),
      prisma.appointment.count({ where: { status: { in: ['PENDING', 'CONFIRMED'] } } }),
      prisma.organisationWallet.aggregate({ _sum: { balance: true } }),
      prisma.product.count({ where: { isActive: true } }),
    ]);

  const totalWalletBalance = Number(walletSum._sum.balance ?? 0);

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <StatsCard
        title="Organisations"
        value={orgCount}
        icon={Building2}
        iconBg="bg-blue-50"
        iconColor="text-blue-600"
      />
      <StatsCard
        title="Active Doctors"
        value={doctorCount.toLocaleString()}
        subtitle="Verified & active"
        icon={Stethoscope}
        iconBg="bg-emerald-50"
        iconColor="text-emerald-600"
      />
      <StatsCard
        title="Patients"
        value={patientCount.toLocaleString()}
        icon={Users}
        iconBg="bg-purple-50"
        iconColor="text-purple-600"
      />
      <StatsCard
        title="Active Bookings"
        value={bookingCount}
        subtitle="Pending & confirmed"
        icon={Calendar}
        iconBg="bg-amber-50"
        iconColor="text-amber-600"
      />
      <StatsCard
        title="Total Wallet Balance"
        value={formatCurrency(totalWalletBalance)}
        subtitle="Across all organisations"
        icon={Wallet}
        iconBg="bg-primary-50"
        iconColor="text-primary-600"
      />
      <StatsCard
        title="Active Products"
        value={productCount.toLocaleString()}
        subtitle="Marketplace SKUs"
        icon={ShoppingBag}
        iconBg="bg-rose-50"
        iconColor="text-rose-600"
      />
    </div>
  );
}

async function RecentBookings() {
  const appointments = await prisma.appointment.findMany({
    take: 8,
    orderBy: { createdAt: 'desc' },
    include: {
      patient: { include: { user: { select: { name: true } } } },
      doctor: { include: { user: { select: { name: true } } } },
    },
  });

  return (
    <Card>
      <CardHeader>
        <CardTitle>Recent Bookings</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-100 text-left text-gray-500">
                <th className="pb-3 font-medium">Patient</th>
                <th className="pb-3 font-medium">Doctor</th>
                <th className="pb-3 font-medium">Type</th>
                <th className="pb-3 font-medium">Scheduled</th>
                <th className="pb-3 font-medium">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {appointments.map((appt) => (
                <tr key={appt.id} className="hover:bg-gray-50">
                  <td className="py-3 font-medium text-gray-900">{appt.patient.user.name}</td>
                  <td className="py-3 text-gray-600">Dr. {appt.doctor.user.name}</td>
                  <td className="py-3">
                    <Badge variant={appt.type === 'VISUAL_CONSULTATION' ? 'primary' : 'default'}>
                      {appt.type.replace(/_/g, ' ')}
                    </Badge>
                  </td>
                  <td className="py-3 text-gray-600">{formatDateTime(appt.scheduledAt)}</td>
                  <td className="py-3">
                    <AppointmentStatusBadge status={appt.status} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  );
}

async function RecentOrganisations() {
  const orgs = await prisma.organisation.findMany({
    take: 5,
    orderBy: { createdAt: 'desc' },
    where: { deletedAt: null },
    include: {
      wallet: { select: { balance: true } },
      _count: { select: { staff: true } },
    },
  });

  return (
    <Card>
      <CardHeader>
        <CardTitle>Recent Organisations</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          {orgs.map((org) => (
            <div key={org.id} className="flex items-center justify-between rounded-lg border border-gray-100 p-3">
              <div>
                <p className="font-medium text-gray-900">{org.name}</p>
                <p className="text-xs text-gray-500">{org._count.staff} staff members</p>
              </div>
              <div className="text-right">
                <p className="font-semibold text-gray-900">
                  {formatCurrency(Number(org.wallet?.balance ?? 0))}
                </p>
                <p className="text-xs text-gray-500">wallet balance</p>
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}

export default async function AdminDashboard() {
  await requireAuth(['SUPER_ADMIN']);

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">Platform Overview</h2>
        <p className="text-sm text-gray-500">Real-time platform metrics and activity</p>
      </div>

      <Suspense fallback={<div className="h-32 animate-pulse rounded-xl bg-gray-100" />}>
        <AdminStats />
      </Suspense>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Suspense fallback={<div className="h-64 animate-pulse rounded-xl bg-gray-100" />}>
          <RecentBookings />
        </Suspense>
        <Suspense fallback={<div className="h-64 animate-pulse rounded-xl bg-gray-100" />}>
          <RecentOrganisations />
        </Suspense>
      </div>
    </div>
  );
}
