import { Suspense } from 'react';
import Link from 'next/link';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatCurrency, formatDate } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { SearchBar } from '@/components/ui/search-bar';
import { ActionButton } from '@/components/ui/action-button';
import { Building2, Plus } from 'lucide-react';

interface Props {
  searchParams: { search?: string; page?: string };
}

async function OrgTable({ search, page }: { search: string; page: number }) {
  const pageSize = 20;
  const where = search
    ? {
        deletedAt: null,
        OR: [
          { name: { contains: search, mode: 'insensitive' as const } },
          { email: { contains: search, mode: 'insensitive' as const } },
        ],
      }
    : { deletedAt: null };

  const [orgs, total] = await Promise.all([
    prisma.organisation.findMany({
      where,
      include: {
        adminUser: { select: { name: true, email: true } },
        wallet: { select: { balance: true, currency: true } },
        _count: { select: { staff: true } },
      },
      orderBy: { createdAt: 'desc' },
      skip: (page - 1) * pageSize,
      take: pageSize,
    }),
    prisma.organisation.count({ where }),
  ]);

  if (orgs.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
        <Building2 className="mx-auto h-10 w-10 text-gray-300" />
        <p className="mt-3 text-sm text-gray-500">No organisations found</p>
      </div>
    );
  }

  return (
    <>
      <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50">
            <tr>
              {['Organisation', 'Admin', 'Staff', 'Wallet Balance', 'Joined', 'Status', ''].map(
                (h) => (
                  <th
                    key={h}
                    className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                  >
                    {h}
                  </th>
                )
              )}
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {orgs.map((org) => (
              <tr key={org.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-medium text-gray-900">{org.name}</td>
                <td className="px-4 py-3 text-gray-500">
                  <div>{org.adminUser?.name ?? '—'}</div>
                  <div className="text-xs">{org.adminUser?.email}</div>
                </td>
                <td className="px-4 py-3 text-gray-500">{org._count.staff}</td>
                <td className="px-4 py-3 font-medium text-gray-900">
                  {org.wallet
                    ? formatCurrency(Number(org.wallet.balance), org.wallet.currency)
                    : '—'}
                </td>
                <td className="px-4 py-3 text-gray-500">{formatDate(org.createdAt)}</td>
                <td className="px-4 py-3">
                  <Badge variant={org.isActive ? 'success' : 'danger'}>
                    {org.isActive ? 'Active' : 'Inactive'}
                  </Badge>
                </td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-2">
                    <Link
                      href={`/admin/organisations/${org.id}`}
                      className="text-xs text-primary-600 hover:underline"
                    >
                      View
                    </Link>
                    <ActionButton
                      url={`/api/organisations/${org.id}`}
                      method="PATCH"
                      body={{ isActive: !org.isActive }}
                      confirm={`${org.isActive ? 'Deactivate' : 'Activate'} ${org.name}?`}
                      variant={org.isActive ? 'danger' : 'success'}
                      size="sm"
                    >
                      {org.isActive ? 'Deactivate' : 'Activate'}
                    </ActionButton>
                  </div>
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
        <div className="flex gap-2">
          {page > 1 && (
            <Link
              href={`?page=${page - 1}${search ? `&search=${search}` : ''}`}
              className="rounded border px-3 py-1 hover:bg-gray-100"
            >
              Prev
            </Link>
          )}
          {page * pageSize < total && (
            <Link
              href={`?page=${page + 1}${search ? `&search=${search}` : ''}`}
              className="rounded border px-3 py-1 hover:bg-gray-100"
            >
              Next
            </Link>
          )}
        </div>
      </div>
    </>
  );
}

export default async function AdminOrganisationsPage({ searchParams }: Props) {
  await requireAuth(['SUPER_ADMIN']);
  const search = searchParams.search ?? '';
  const page = Math.max(1, parseInt(searchParams.page ?? '1'));

  return (
    <div>
      <PageHeader
        title="Organisations"
        description="Manage all registered healthcare organisations"
        action={
          <Link
            href="/admin/organisations/new"
            className="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
          >
            <Plus className="h-4 w-4" /> New Organisation
          </Link>
        }
      />
      <div className="mb-4">
        <Suspense>
          <SearchBar placeholder="Search by name or email…" />
        </Suspense>
      </div>
      <Suspense fallback={<div className="py-8 text-center text-gray-400 text-sm">Loading…</div>}>
        <OrgTable search={search} page={page} />
      </Suspense>
    </div>
  );
}
