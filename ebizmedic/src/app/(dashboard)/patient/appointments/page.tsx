import { Suspense } from 'react';
import Link from 'next/link';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDateTime } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { ActionButton } from '@/components/ui/action-button';
import { Calendar, Plus, Video } from 'lucide-react';

const STATUS_BADGE: Record<string, 'success' | 'warning' | 'danger' | 'secondary' | 'info'> = {
  PENDING: 'warning',
  CONFIRMED: 'info',
  COMPLETED: 'success',
  CANCELLED: 'danger',
  NO_SHOW: 'danger',
  RESERVED: 'secondary',
};

async function AppointmentList({ patientId }: { patientId: string }) {
  const appointments = await prisma.appointment.findMany({
    where: { patientId },
    include: {
      doctor: {
        include: {
          user: { select: { name: true, avatarUrl: true } },
          specialties: { where: { isPrimary: true }, take: 1 },
        },
      },
      videoMeeting: { select: { joinUrl: true } },
    },
    orderBy: { scheduledAt: 'desc' },
    take: 50,
  });

  if (appointments.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
        <Calendar className="mx-auto h-10 w-10 text-gray-300" />
        <p className="mt-3 text-sm text-gray-500">No appointments yet</p>
        <Link
          href="/patient/book"
          className="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
        >
          <Plus className="h-4 w-4" /> Book Now
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {appointments.map((appt) => (
        <div key={appt.id} className="rounded-xl border border-gray-200 bg-white p-4">
          <div className="flex items-start justify-between gap-3">
            <div className="flex-1">
              <div className="flex items-center gap-2">
                <span className="font-semibold text-gray-900">Dr. {appt.doctor.user.name}</span>
                <Badge variant={STATUS_BADGE[appt.status] ?? 'secondary'} className="text-xs">
                  {appt.status}
                </Badge>
              </div>
              <p className="mt-0.5 text-sm text-gray-500">
                {appt.doctor.specialties[0]?.specialty ?? 'General Practice'}
              </p>
              <p className="mt-1 text-sm font-medium text-gray-700">
                {formatDateTime(appt.scheduledAt)} · {appt.durationMinutes} min · {appt.type.replace(/_/g, ' ')}
              </p>
              {appt.notes && <p className="mt-1 text-xs text-gray-400">{appt.notes}</p>}
            </div>
            <div className="flex shrink-0 flex-col items-end gap-2">
              {appt.videoMeeting?.joinUrl && appt.status === 'CONFIRMED' && (
                <Link
                  href={appt.videoMeeting.joinUrl}
                  target="_blank"
                  className="inline-flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700"
                >
                  <Video className="h-3 w-3" /> Join Call
                </Link>
              )}
              {(appt.status === 'PENDING' || appt.status === 'CONFIRMED') && (
                <ActionButton
                  url={`/api/bookings/${appt.id}`}
                  method="PATCH"
                  body={{ action: 'cancel' }}
                  confirm="Cancel this appointment?"
                  variant="danger"
                  size="sm"
                >
                  Cancel
                </ActionButton>
              )}
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}

export default async function PatientAppointmentsPage() {
  const user = await requireAuth(['PATIENT', 'SUPER_ADMIN']);
  const patient = await prisma.patientProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
  if (!patient) return <p className="p-6 text-gray-500">Patient profile not found.</p>;

  return (
    <div>
      <PageHeader
        title="My Appointments"
        description="View and manage your consultations"
        action={
          <Link
            href="/patient/book"
            className="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
          >
            <Plus className="h-4 w-4" /> Book New
          </Link>
        }
      />
      <Suspense fallback={<div className="py-8 text-center text-sm text-gray-400">Loading…</div>}>
        <AppointmentList patientId={patient.id} />
      </Suspense>
    </div>
  );
}
