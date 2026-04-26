import Link from 'next/link';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDateTime } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { Calendar } from 'lucide-react';

const STATUS_BADGE: Record<string, 'success' | 'warning' | 'danger' | 'secondary' | 'info'> = {
  PENDING: 'warning', CONFIRMED: 'info', COMPLETED: 'success',
  CANCELLED: 'danger', NO_SHOW: 'danger', RESERVED: 'secondary',
};

interface Props { searchParams: { status?: string } }

export default async function OrgBookingsPage({ searchParams }: Props) {
  const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);
  const status = searchParams.status ?? '';

  const org = await prisma.organisation.findFirst({
    where: user.role === 'ORG_ADMIN' ? { adminUserId: user.id } : {},
    select: { id: true },
  });
  if (!org) return <p className="p-6 text-gray-500">Organisation not found.</p>;

  const staffUserIds = await prisma.organisationStaff.findMany({
    where: { organisationId: org.id },
    select: { userId: true },
  });
  const userIds = staffUserIds.map((s) => s.userId);

  const where: Record<string, unknown> = {
    patient: { userId: { in: userIds } },
  };
  if (status) where.status = status;

  const appointments = await prisma.appointment.findMany({
    where,
    include: {
      patient: { include: { user: { select: { name: true } } } },
      doctor: { include: { user: { select: { name: true } } } },
    },
    orderBy: { scheduledAt: 'desc' },
    take: 100,
  });

  const statuses = ['', 'PENDING', 'CONFIRMED', 'COMPLETED', 'CANCELLED', 'NO_SHOW'];

  return (
    <div>
      <PageHeader title="Staff Bookings" description="All consultations booked by your staff" />
      <div className="mb-4 flex flex-wrap gap-2">
        {statuses.map((s) => (
          <Link key={s} href={s ? `?status=${s}` : '?'}
            className={`rounded-full px-3 py-1 text-xs font-medium ${status === s ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}>
            {s || 'All'}
          </Link>
        ))}
      </div>

      {appointments.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <Calendar className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No bookings found</p>
        </div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['Staff Member', 'Doctor', 'Type', 'Scheduled', 'Status'].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {appointments.map((a) => (
                <tr key={a.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-900">{a.patient.user.name}</td>
                  <td className="px-4 py-3 text-gray-500">Dr. {a.doctor.user.name}</td>
                  <td className="px-4 py-3 text-gray-500">{a.type.replace(/_/g, ' ')}</td>
                  <td className="px-4 py-3 text-gray-500">{formatDateTime(a.scheduledAt)}</td>
                  <td className="px-4 py-3">
                    <Badge variant={STATUS_BADGE[a.status] ?? 'secondary'}>{a.status}</Badge>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
