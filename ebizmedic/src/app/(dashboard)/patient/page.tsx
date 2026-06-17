import { Suspense } from 'react';
import Link from 'next/link';
import { requireAuth } from '@/lib/auth';
import { prisma } from '@/lib/db';
import { StatsCard } from '@/components/dashboard/stats-card';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge, AppointmentStatusBadge, OrderStatusBadge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar, ShoppingBag, FileText, Video, Plus } from 'lucide-react';
import { formatDateTime, formatDate } from '@/lib/utils';

export const metadata = { title: 'My Dashboard' };

async function PatientDashboardContent() {
  const user = await requireAuth(['PATIENT', 'SUPER_ADMIN']);

  const patient = await prisma.patientProfile.findUnique({
    where: { userId: user.id },
    include: { _count: { select: { appointments: true, orders: true } } },
  });

  if (!patient) {
    return (
      <div className="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center">
        <p className="font-semibold text-amber-700">Patient profile not set up</p>
        <p className="mt-1 text-sm text-amber-600">Please contact support.</p>
      </div>
    );
  }

  const [upcomingAppointments, recentOrders, activePrescriptions] = await Promise.all([
    prisma.appointment.findMany({
      where: {
        patientId: patient.id,
        status: { in: ['PENDING', 'CONFIRMED'] },
        scheduledAt: { gte: new Date() },
      },
      include: {
        doctor: {
          include: {
            user: { select: { name: true, avatarUrl: true } },
            specialties: { where: { isPrimary: true }, take: 1 },
          },
        },
        videoMeeting: { select: { joinUrl: true, password: true } },
      },
      orderBy: { scheduledAt: 'asc' },
      take: 3,
    }),
    prisma.order.findMany({
      where: { patientId: patient.id },
      include: { dispensary: { select: { name: true } }, _count: { select: { items: true } } },
      orderBy: { createdAt: 'desc' },
      take: 3,
    }),
    prisma.prescription.count({
      where: { patientUserId: user.id, isDispensed: false, expiresAt: { gt: new Date() } },
    }),
  ]);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-xl font-bold text-gray-900">Hi, {user.name}</h2>
          <p className="text-sm text-gray-500">Your health dashboard</p>
        </div>
        <Link href="/patient/book">
          <Button size="sm">
            <Plus className="h-4 w-4" />
            Book Consultation
          </Button>
        </Link>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatsCard
          title="Total Appointments"
          value={patient._count.appointments}
          icon={Calendar}
        />
        <StatsCard
          title="Total Orders"
          value={patient._count.orders}
          icon={ShoppingBag}
          iconBg="bg-emerald-50"
          iconColor="text-emerald-600"
        />
        <StatsCard
          title="Active Prescriptions"
          value={activePrescriptions}
          icon={FileText}
          iconBg="bg-purple-50"
          iconColor="text-purple-600"
        />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Upcoming appointments */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Upcoming Appointments</CardTitle>
            <Link href="/patient/appointments" className="text-xs text-primary-600 hover:underline">
              View all
            </Link>
          </CardHeader>
          <CardContent>
            {upcomingAppointments.length ? (
              <div className="space-y-3">
                {upcomingAppointments.map((appt) => (
                  <div key={appt.id} className="rounded-lg border border-gray-100 p-3">
                    <div className="flex items-start justify-between">
                      <div>
                        <p className="font-medium text-gray-900">
                          Dr. {appt.doctor.user.name}
                        </p>
                        <p className="text-xs text-gray-500">
                          {appt.doctor.specialties[0]?.specialty ?? 'General'}
                        </p>
                        <p className="mt-1 text-sm text-gray-600">{formatDateTime(appt.scheduledAt)}</p>
                      </div>
                      <AppointmentStatusBadge status={appt.status} />
                    </div>
                    {appt.videoMeeting && appt.status === 'CONFIRMED' && (
                      <a
                        href={appt.videoMeeting.joinUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mt-2 flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:underline"
                      >
                        <Video className="h-3.5 w-3.5" />
                        Join video consultation
                      </a>
                    )}
                  </div>
                ))}
              </div>
            ) : (
              <div className="py-6 text-center">
                <Calendar className="mx-auto h-8 w-8 text-gray-300" />
                <p className="mt-2 text-sm text-gray-500">No upcoming appointments</p>
                <Link href="/patient/book">
                  <Button variant="secondary" size="sm" className="mt-3">
                    Book now
                  </Button>
                </Link>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Recent orders */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Recent Orders</CardTitle>
            <Link href="/patient/orders" className="text-xs text-primary-600 hover:underline">
              View all
            </Link>
          </CardHeader>
          <CardContent>
            {recentOrders.length ? (
              <div className="space-y-3">
                {recentOrders.map((order) => (
                  <div key={order.id} className="flex items-center justify-between rounded-lg border border-gray-100 p-3">
                    <div>
                      <p className="font-medium text-gray-900">{order.dispensary.name}</p>
                      <p className="text-xs text-gray-500">
                        {order._count.items} item{order._count.items !== 1 ? 's' : ''} · {formatDate(order.createdAt)}
                      </p>
                    </div>
                    <OrderStatusBadge status={order.status} />
                  </div>
                ))}
              </div>
            ) : (
              <div className="py-6 text-center">
                <ShoppingBag className="mx-auto h-8 w-8 text-gray-300" />
                <p className="mt-2 text-sm text-gray-500">No orders yet</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

export default async function PatientDashboard() {
  return (
    <Suspense fallback={<div className="h-96 animate-pulse rounded-xl bg-gray-100" />}>
      <PatientDashboardContent />
    </Suspense>
  );
}
