import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDateTime } from '@/lib/utils';
import { PageHeader } from '@/components/ui/page-header';
import { Shield } from 'lucide-react';

interface Props { searchParams: { page?: string; resource?: string } }

export default async function AdminAuditPage({ searchParams }: Props) {
  await requireAuth(['SUPER_ADMIN']);
  const page = Math.max(1, parseInt(searchParams.page ?? '1'));
  const pageSize = 30;
  const resource = searchParams.resource ?? '';

  const where = resource ? { resource } : {};
  const [logs, total] = await Promise.all([
    prisma.auditLog.findMany({
      where,
      include: { user: { select: { name: true, email: true } } },
      orderBy: { createdAt: 'desc' },
      skip: (page - 1) * pageSize,
      take: pageSize,
    }),
    prisma.auditLog.count({ where }),
  ]);

  const resources = await prisma.auditLog.findMany({
    distinct: ['resource'],
    select: { resource: true },
    orderBy: { resource: 'asc' },
  });

  const ACTION_COLOUR: Record<string, string> = {
    CREATE: 'text-emerald-600',
    UPDATE: 'text-blue-600',
    DELETE: 'text-red-600',
    LOGIN: 'text-purple-600',
  };

  return (
    <div>
      <PageHeader title="Audit Logs" description="Full platform activity trail" />

      <div className="mb-4 flex flex-wrap gap-2">
        {[{ resource: '' }, ...resources].map(({ resource: r }) => (
          <a key={r}
            href={r ? `?resource=${r}` : '?'}
            className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${resource === r ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}>
            {r || 'All'}
          </a>
        ))}
      </div>

      {logs.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <Shield className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No audit logs yet</p>
        </div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['When', 'User', 'Action', 'Resource', 'ID'].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {logs.map((log) => (
                <tr key={log.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-gray-400">{formatDateTime(log.createdAt)}</td>
                  <td className="px-4 py-3 text-gray-700">
                    {log.user ? (
                      <span>{log.user.name}<span className="ml-1 text-xs text-gray-400">{log.user.email}</span></span>
                    ) : <span className="text-gray-400">System</span>}
                  </td>
                  <td className={`px-4 py-3 font-semibold ${ACTION_COLOUR[log.action] ?? 'text-gray-600'}`}>
                    {log.action}
                  </td>
                  <td className="px-4 py-3 text-gray-500">{log.resource}</td>
                  <td className="px-4 py-3 font-mono text-xs text-gray-400">{log.resourceId?.slice(-8) ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="flex items-center justify-between border-t px-4 py-3 text-xs text-gray-500">
            <span>{(page - 1) * pageSize + 1}–{Math.min(page * pageSize, total)} of {total}</span>
            <div className="flex gap-2">
              {page > 1 && <a href={`?page=${page - 1}${resource ? `&resource=${resource}` : ''}`} className="rounded border px-3 py-1 hover:bg-gray-100">Prev</a>}
              {page * pageSize < total && <a href={`?page=${page + 1}${resource ? `&resource=${resource}` : ''}`} className="rounded border px-3 py-1 hover:bg-gray-100">Next</a>}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
