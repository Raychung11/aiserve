import { Suspense } from 'react';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDateTime } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { ActionButton } from '@/components/ui/action-button';
import { Calendar, Video } from 'lucide-react';
import Link from 'next/link';

const STATUS_BADGE: Record<string, 'success' | 'warning' | 'danger' | 'secondary' | 'info'> = {
  PENDING: 'warning',
  CONFIRMED: 'info',
  COMPLETED: 'success',
  CANCELLED: 'danger',
  NO_SHOW: 'danger',
  RESERVED: 'secondary',
};

interface Props {
  searchParams: { status?: string };
}

async function AppointmentTable({ doctorId, status }: { doctorId: string; status?: string }) {
  const where = {
    doctorId,
    ...(status ? { status } : {}),
  };

  const appointments = await prisma.appointment.findMany({
    where,
    include: {
      patient: { include: { user: { select: { name: true, email: true, phone: true } } } },
      videoMeeting: { select: { joinUrl: true, status: true } },
    },
    orderBy: { scheduledAt: 'asc' },
    take: 50,
  });

  if (appointments.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
        <Calendar className="mx-auto h-10 w-10 text-gray-300" />
        <p className="mt-3 text-sm text-gray-500">No appointments found</p>
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
      <table className="min-w-full divide-y divide-gray-200 text-sm">
        <thead className="bg-gray-50">
          <tr>
            {['Patient', 'Type', 'Scheduled', 'Duration', 'Status', 'Actions'].map((h) => (
              <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                {h}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100">
          {appointments.map((appt) => (
            <tr key={appt.id} className="hover:bg-gray-50">
              <td className="px-4 py-3">
                <div className="font-medium text-gray-900">{appt.patient.user.name}</div>
                <div className="text-xs text-gray-400">{appt.patient.user.email}</div>
              </td>
              <td className="px-4 py-3 text-gray-500">{appt.type.replace(/_/g, ' ')}</td>
              <td className="px-4 py-3 text-gray-500">{formatDateTime(appt.scheduledAt)}</td>
              <td className="px-4 py-3 text-gray-500">{appt.durationMinutes} min</td>
              <td className="px-4 py-3">
                <Badge variant={STATUS_BADGE[appt.status] ?? 'secondary'}>{appt.status}</Badge>
              </td>
              <td className="px-4 py-3">
                <div className="flex items-center gap-2">
                  {appt.videoMeeting?.joinUrl && appt.status === 'CONFIRMED' && (
                    <Link
                      href={appt.videoMeeting.joinUrl}
                      target="_blank"
                      className="inline-flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700"
                    >
                      <Video className="h-3 w-3" /> Join
                    </Link>
                  )}
                  {appt.status === 'PENDING' && (
                    <ActionButton
                      url={`/api/bookings/${appt.id}`}
                      method="PATCH"
                      body={{ status: 'CONFIRMED' }}
                      variant="success"
                      size="sm"
                    >
                      Confirm
                    </ActionButton>
                  )}
                  {appt.status === 'CONFIRMED' && (
                    <ActionButton
                      url={`/api/bookings/${appt.id}`}
                      method="PATCH"
                      body={{ status: 'COMPLETED' }}
                      confirm="Mark this appointment as completed?"
                      variant="success"
                      size="sm"
                    >
                      Complete
                    </ActionButton>
                  )}
                  {(appt.status === 'PENDING' || appt.status === 'CONFIRMED') && (
                    <ActionButton
                      url={`/api/bookings/${appt.id}`}
                      method="PATCH"
                      body={{ status: 'NO_SHOW' }}
                      confirm="Mark patient as no-show?"
                      variant="danger"
                      size="sm"
                    >
                      No-show
                    </ActionButton>
                  )}
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default async function DoctorAppointmentsPage({ searchParams }: Props) {
  const user = await requireAuth(['DOCTOR', 'SUPER_ADMIN']);
  const doc = await prisma.doctorProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
  if (!doc) return <p className="p-6 text-gray-500">Doctor profile not found.</p>;

  const statuses = ['', 'PENDING', 'CONFIRMED', 'COMPLETED', 'CANCELLED', 'NO_SHOW'];
  const activeStatus = searchParams.status ?? '';

  return (
    <div>
      <PageHeader title="My Appointments" description="Manage and action your upcoming consultations" />
      <div className="mb-4 flex flex-wrap gap-2">
        {statuses.map((s) => (
          <Link
            key={s}
            href={s ? `?status=${s}` : '?'}
            className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${
              activeStatus === s
                ? 'bg-primary-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            {s || 'All'}
          </Link>
        ))}
      </div>
      <Suspense fallback={<div className="py-8 text-center text-sm text-gray-400">Loading…</div>}>
        <AppointmentTable doctorId={doc.id} status={activeStatus || undefined} />
      </Suspense>
    </div>
  );
}
