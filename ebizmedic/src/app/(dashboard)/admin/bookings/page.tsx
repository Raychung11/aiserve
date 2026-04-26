import { Suspense } from 'react';
import Link from 'next/link';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDateTime } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { SearchBar } from '@/components/ui/search-bar';
import { Calendar } from 'lucide-react';

const STATUS_BADGE: Record<string, 'success' | 'warning' | 'danger' | 'secondary' | 'info'> = {
  PENDING: 'warning', CONFIRMED: 'info', COMPLETED: 'success',
  CANCELLED: 'danger', NO_SHOW: 'danger', RESERVED: 'secondary',
};

interface Props { searchParams: { search?: string; status?: string; page?: string } }

async function BookingTable({ search, status, page }: { search: string; status: string; page: number }) {
  const pageSize = 25;
  const where: Record<string, unknown> = {};
  if (status) where.status = status;
  if (search) {
    where.OR = [
      { patient: { user: { name: { contains: search, mode: 'insensitive' } } } },
      { doctor: { user: { name: { contains: search, mode: 'insensitive' } } } },
      { bookingRef: { contains: search, mode: 'insensitive' } },
    ];
  }

  const [appointments, total] = await Promise.all([
    prisma.appointment.findMany({
      where,
      include: {
        patient: { include: { user: { select: { name: true } } } },
        doctor: { include: { user: { select: { name: true } } } },
        videoMeeting: { select: { status: true } },
      },
      orderBy: { scheduledAt: 'desc' },
      skip: (page - 1) * pageSize,
      take: pageSize,
    }),
    prisma.appointment.count({ where }),
  ]);

  if (appointments.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
        <Calendar className="mx-auto h-10 w-10 text-gray-300" />
        <p className="mt-3 text-sm text-gray-500">No bookings found</p>
      </div>
    );
  }

  return (
    <>
      <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50">
            <tr>
              {['Ref', 'Patient', 'Doctor', 'Type', 'Scheduled', 'Duration', 'Status'].map((h) => (
                <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {appointments.map((a) => (
              <tr key={a.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-mono text-xs text-gray-400">{a.bookingRef.slice(-8).toUpperCase()}</td>
                <td className="px-4 py-3 font-medium text-gray-900">{a.patient.user.name}</td>
                <td className="px-4 py-3 text-gray-500">Dr. {a.doctor.user.name}</td>
                <td className="px-4 py-3 text-gray-500">{a.type.replace(/_/g, ' ')}</td>
                <td className="px-4 py-3 text-gray-500">{formatDateTime(a.scheduledAt)}</td>
                <td className="px-4 py-3 text-gray-500">{a.durationMinutes} min</td>
                <td className="px-4 py-3">
                  <Badge variant={STATUS_BADGE[a.status] ?? 'secondary'}>{a.status}</Badge>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="mt-4 flex items-center justify-between text-xs text-gray-500">
        <span>{(page - 1) * pageSize + 1}–{Math.min(page * pageSize, total)} of {total}</span>
        <div className="flex gap-2">
          {page > 1 && <Link href={`?page=${page - 1}${status ? `&status=${status}` : ''}${search ? `&search=${search}` : ''}`} className="rounded border px-3 py-1 hover:bg-gray-100">Prev</Link>}
          {page * pageSize < total && <Link href={`?page=${page + 1}${status ? `&status=${status}` : ''}${search ? `&search=${search}` : ''}`} className="rounded border px-3 py-1 hover:bg-gray-100">Next</Link>}
        </div>
      </div>
    </>
  );
}

export default async function AdminBookingsPage({ searchParams }: Props) {
  await requireAuth(['SUPER_ADMIN']);
  const search = searchParams.search ?? '';
  const status = searchParams.status ?? '';
  const page = Math.max(1, parseInt(searchParams.page ?? '1'));
  const statuses = ['', 'PENDING', 'CONFIRMED', 'COMPLETED', 'CANCELLED', 'NO_SHOW'];

  return (
    <div>
      <PageHeader title="All Bookings" description="Platform-wide appointment overview" />
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <Suspense><SearchBar placeholder="Search patient, doctor, or ref…" /></Suspense>
        <div className="flex flex-wrap gap-2">
          {statuses.map((s) => (
            <Link key={s} href={s ? `?status=${s}` : '?'}
              className={`rounded-full px-3 py-1 text-xs font-medium ${status === s ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}>
              {s || 'All'}
            </Link>
          ))}
        </div>
      </div>
      <Suspense fallback={<div className="py-8 text-center text-sm text-gray-400">Loading…</div>}>
        <BookingTable search={search} status={status} page={page} />
      </Suspense>
    </div>
  );
}
