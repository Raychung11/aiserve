import { Suspense } from 'react';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDate } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { SearchBar } from '@/components/ui/search-bar';
import { ActionButton } from '@/components/ui/action-button';
import { Stethoscope } from 'lucide-react';

interface Props {
  searchParams: { search?: string; page?: string };
}

async function DoctorTable({ search, page }: { search: string; page: number }) {
  const pageSize = 20;
  const where = search
    ? {
        user: {
          OR: [
            { name: { contains: search, mode: 'insensitive' as const } },
            { email: { contains: search, mode: 'insensitive' as const } },
          ],
        },
      }
    : {};

  const [doctors, total] = await Promise.all([
    prisma.doctorProfile.findMany({
      where,
      include: {
        user: { select: { name: true, email: true, avatarUrl: true } },
        specialties: { where: { isPrimary: true }, take: 1 },
        _count: { select: { appointments: true } },
      },
      orderBy: { createdAt: 'desc' },
      skip: (page - 1) * pageSize,
      take: pageSize,
    }),
    prisma.doctorProfile.count({ where }),
  ]);

  if (doctors.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
        <Stethoscope className="mx-auto h-10 w-10 text-gray-300" />
        <p className="mt-3 text-sm text-gray-500">No doctors found</p>
      </div>
    );
  }

  return (
    <>
      <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50">
            <tr>
              {['Doctor', 'Specialty', 'Reg. No.', 'Appointments', 'Joined', 'Status', 'Verified', ''].map((h) => (
                <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {doctors.map((doc) => (
              <tr key={doc.id} className="hover:bg-gray-50">
                <td className="px-4 py-3">
                  <div className="font-medium text-gray-900">{doc.user.name}</div>
                  <div className="text-xs text-gray-400">{doc.user.email}</div>
                </td>
                <td className="px-4 py-3 text-gray-500">
                  {doc.specialties[0]?.specialty ?? '—'}
                </td>
                <td className="px-4 py-3 font-mono text-xs text-gray-500">{doc.licenseNo ?? '—'}</td>
                <td className="px-4 py-3 text-gray-500">{doc._count.appointments}</td>
                <td className="px-4 py-3 text-gray-500">{formatDate(doc.createdAt)}</td>
                <td className="px-4 py-3">
                  <Badge variant={doc.isAvailableOnline || doc.isAvailableOnsite ? 'success' : 'warning'}>
                    {doc.isAvailableOnline && doc.isAvailableOnsite ? 'Online & Onsite' : doc.isAvailableOnline ? 'Online' : doc.isAvailableOnsite ? 'Onsite' : 'Unavailable'}
                  </Badge>
                </td>
                <td className="px-4 py-3">
                  <Badge variant={doc.isVerified ? 'success' : 'secondary'}>
                    {doc.isVerified ? 'Verified' : 'Pending'}
                  </Badge>
                </td>
                <td className="px-4 py-3">
                  {!doc.isVerified && (
                    <ActionButton
                      url={`/api/doctors/${doc.id}`}
                      method="PATCH"
                      body={{ isVerified: true }}
                      confirm={`Verify Dr. ${doc.user.name}?`}
                      variant="success"
                      size="sm"
                    >
                      Verify
                    </ActionButton>
                  )}
                  {doc.isVerified && (
                    <ActionButton
                      url={`/api/doctors/${doc.id}`}
                      method="PATCH"
                      body={{ isVerified: false }}
                      confirm={`Revoke verification for Dr. ${doc.user.name}?`}
                      variant="danger"
                      size="sm"
                    >
                      Revoke
                    </ActionButton>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="mt-4 flex items-center justify-between text-xs text-gray-500">
        <span>
          {(page - 1) * pageSize + 1}–{Math.min(page * pageSize, total)} of {total}
        </span>
      </div>
    </>
  );
}

export default async function AdminDoctorsPage({ searchParams }: Props) {
  await requireAuth(['SUPER_ADMIN']);
  const search = searchParams.search ?? '';
  const page = Math.max(1, parseInt(searchParams.page ?? '1'));

  return (
    <div>
      <PageHeader title="Doctors" description="View and verify all registered doctors" />
      <div className="mb-4">
        <Suspense>
          <SearchBar placeholder="Search by name or email…" />
        </Suspense>
      </div>
      <Suspense fallback={<div className="py-8 text-center text-gray-400 text-sm">Loading…</div>}>
        <DoctorTable search={search} page={page} />
      </Suspense>
    </div>
  );
}
