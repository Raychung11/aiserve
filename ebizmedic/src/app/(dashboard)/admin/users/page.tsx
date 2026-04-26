import { Suspense } from 'react';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDate } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { SearchBar } from '@/components/ui/search-bar';
import { ActionButton } from '@/components/ui/action-button';
import { ROLE_LABELS } from '@/types/roles';
import { Users } from 'lucide-react';
import type { UserRole } from '@/types';

interface Props {
  searchParams: { search?: string; page?: string };
}

async function UserTable({ search, page }: { search: string; page: number }) {
  const pageSize = 25;
  const where = search
    ? {
        deletedAt: null,
        OR: [
          { name: { contains: search, mode: 'insensitive' as const } },
          { email: { contains: search, mode: 'insensitive' as const } },
        ],
      }
    : { deletedAt: null };

  const [users, total] = await Promise.all([
    prisma.user.findMany({
      where,
      select: { id: true, name: true, email: true, role: true, isActive: true, createdAt: true, lastLoginAt: true },
      orderBy: { createdAt: 'desc' },
      skip: (page - 1) * pageSize,
      take: pageSize,
    }),
    prisma.user.count({ where }),
  ]);

  if (users.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
        <Users className="mx-auto h-10 w-10 text-gray-300" />
        <p className="mt-3 text-sm text-gray-500">No users found</p>
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
      <table className="min-w-full divide-y divide-gray-200 text-sm">
        <thead className="bg-gray-50">
          <tr>
            {['Name', 'Email', 'Role', 'Joined', 'Last Login', 'Status', ''].map((h) => (
              <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                {h}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100">
          {users.map((u) => (
            <tr key={u.id} className="hover:bg-gray-50">
              <td className="px-4 py-3 font-medium text-gray-900">{u.name}</td>
              <td className="px-4 py-3 text-gray-500">{u.email}</td>
              <td className="px-4 py-3">
                <Badge variant="primary">{ROLE_LABELS[u.role as UserRole] ?? u.role}</Badge>
              </td>
              <td className="px-4 py-3 text-gray-500">{formatDate(u.createdAt)}</td>
              <td className="px-4 py-3 text-gray-500">
                {u.lastLoginAt ? formatDate(u.lastLoginAt) : 'Never'}
              </td>
              <td className="px-4 py-3">
                <Badge variant={u.isActive ? 'success' : 'danger'}>
                  {u.isActive ? 'Active' : 'Suspended'}
                </Badge>
              </td>
              <td className="px-4 py-3">
                <ActionButton
                  url={`/api/admin/users/${u.id}`}
                  method="PATCH"
                  body={{ isActive: !u.isActive }}
                  confirm={`${u.isActive ? 'Suspend' : 'Restore'} ${u.name}?`}
                  variant={u.isActive ? 'danger' : 'success'}
                  size="sm"
                >
                  {u.isActive ? 'Suspend' : 'Restore'}
                </ActionButton>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <div className="flex items-center justify-between border-t px-4 py-3 text-xs text-gray-500">
        <span>{(page - 1) * pageSize + 1}–{Math.min(page * pageSize, total)} of {total}</span>
      </div>
    </div>
  );
}

export default async function AdminUsersPage({ searchParams }: Props) {
  await requireAuth(['SUPER_ADMIN']);
  const search = searchParams.search ?? '';
  const page = Math.max(1, parseInt(searchParams.page ?? '1'));

  return (
    <div>
      <PageHeader title="Users" description="Manage all platform user accounts" />
      <div className="mb-4">
        <Suspense>
          <SearchBar placeholder="Search by name or email…" />
        </Suspense>
      </div>
      <Suspense fallback={<div className="py-8 text-center text-gray-400 text-sm">Loading…</div>}>
        <UserTable search={search} page={page} />
      </Suspense>
    </div>
  );
}
