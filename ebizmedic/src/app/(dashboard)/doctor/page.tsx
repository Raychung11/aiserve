import { Suspense } from 'react';
import { requireAuth } from '@/lib/auth';
import { prisma } from '@/lib/db';
import { StatsCard } from '@/components/dashboard/stats-card';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge, AppointmentStatusBadge } from '@/components/ui/badge';
import { Calendar, Clock, Users, Video } from 'lucide-react';
import { formatDateTime } from '@/lib/utils';

export const metadata = { title: 'Doctor Dashboard' };

async function DoctorDashboardContent() {
  const user = await requireAuth(['DOCTOR', 'SUPER_ADMIN']);

  const doctor = await prisma.doctorProfile.findUnique({
    where: { userId: user.id },
    include: {
      specialties: true,
      _count: { select: { appointments: true } },
    },
  });

  if (!doctor) {
    return (
      <div className="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center">
        <p className="font-semibold text-amber-700">Doctor profile not found</p>
        <p className="mt-1 text-sm text-amber-600">Please complete your profile setup.</p>
      </div>
    );
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);

  const [todayAppointments, upcomingCount, completedCount, videoCount] = await Promise.all([
    prisma.appointment.findMany({
      where: {
        doctorId: doctor.id,
        scheduledAt: { gte: today, lt: tomorrow },
        status: { in: ['PENDING', 'CONFIRMED'] },
      },
      include: {
        patient: { include: { user: { select: { name: true, phone: true } } } },
        videoMeeting: { select: { joinUrl: true } },
      },
      orderBy: { scheduledAt: 'asc' },
    }),
    prisma.appointment.count({
      where: { doctorId: doctor.id, status: { in: ['PENDING', 'CONFIRMED'] } },
    }),
    prisma.appointment.count({
      where: { doctorId: doctor.id, status: 'COMPLETED' },
    }),
    prisma.appointment.count({
      where: { doctorId: doctor.id, type: 'VISUAL_CONSULTATION', status: { in: ['PENDING', 'CONFIRMED'] } },
    }),
  ]);

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">Welcome, Dr. {user.name}</h2>
        <p className="text-sm text-gray-500">
          {doctor.specialties.find((s) => s.isPrimary)?.specialty ?? 'General Practice'}
        </p>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard title="Today's Appointments" value={todayAppointments.length} icon={Calendar} />
        <StatsCard
          title="Upcoming"
          value={upcomingCount}
          subtitle="Pending & confirmed"
          icon={Clock}
          iconBg="bg-amber-50"
          iconColor="text-amber-600"
        />
        <StatsCard
          title="Video Consultations"
          value={videoCount}
          icon={Video}
          iconBg="bg-purple-50"
          iconColor="text-purple-600"
        />
        <StatsCard
          title="Completed"
          value={completedCount}
          icon={Users}
          iconBg="bg-emerald-50"
          iconColor="text-emerald-600"
        />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Today&apos;s Schedule</CardTitle>
        </CardHeader>
        <CardContent>
          {todayAppointments.length ? (
            <div className="space-y-3">
              {todayAppointments.map((appt) => (
                <div
                  key={appt.id}
                  className="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 p-4"
                >
                  <div className="flex items-center gap-4">
                    <div className="text-center">
                      <p className="text-lg font-bold text-gray-900">
                        {new Date(appt.scheduledAt).toLocaleTimeString('en-MY', {
                          hour: '2-digit',
                          minute: '2-digit',
                        })}
                      </p>
                      <p className="text-xs text-gray-500">{appt.durationMinutes}min</p>
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">{appt.patient.user.name}</p>
                      <p className="text-sm text-gray-500">{appt.patient.user.phone ?? 'No phone'}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <Badge variant={appt.type === 'VISUAL_CONSULTATION' ? 'primary' : 'default'}>
                      {appt.type === 'VISUAL_CONSULTATION' ? 'Video' : 'Onsite'}
                    </Badge>
                    <AppointmentStatusBadge status={appt.status} />
                    {appt.videoMeeting && (
                      <a
                        href={appt.videoMeeting.joinUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700"
                      >
                        Join
                      </a>
                    )}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="py-8 text-center">
              <Calendar className="mx-auto h-8 w-8 text-gray-300" />
              <p className="mt-2 text-sm text-gray-500">No appointments today</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

export default async function DoctorDashboard() {
  return (
    <Suspense fallback={<div className="h-96 animate-pulse rounded-xl bg-gray-100" />}>
      <DoctorDashboardContent />
    </Suspense>
  );
}
