import { Suspense } from 'react';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDate } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { ActionButton } from '@/components/ui/action-button';
import { Package, Plus } from 'lucide-react';
import Link from 'next/link';

async function DispensaryTable() {
  const dispensaries = await prisma.dispensary.findMany({
    where: { deletedAt: null },
    include: {
      _count: { select: { products: true, orders: true, staff: true } },
    },
    orderBy: { createdAt: 'desc' },
  });

  if (dispensaries.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
        <Package className="mx-auto h-10 w-10 text-gray-300" />
        <p className="mt-3 text-sm text-gray-500">No dispensaries yet</p>
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
      <table className="min-w-full divide-y divide-gray-200 text-sm">
        <thead className="bg-gray-50">
          <tr>
            {['Name', 'Email', 'Phone', 'Staff', 'Products', 'Orders', 'Status', ''].map((h) => (
              <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{h}</th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100">
          {dispensaries.map((d) => (
            <tr key={d.id} className="hover:bg-gray-50">
              <td className="px-4 py-3 font-medium text-gray-900">{d.name}</td>
              <td className="px-4 py-3 text-gray-500">{d.email ?? '—'}</td>
              <td className="px-4 py-3 text-gray-500">{d.phone ?? '—'}</td>
              <td className="px-4 py-3 text-gray-500">{d._count.staff}</td>
              <td className="px-4 py-3 text-gray-500">{d._count.products}</td>
              <td className="px-4 py-3 text-gray-500">{d._count.orders}</td>
              <td className="px-4 py-3">
                <Badge variant={d.isActive ? 'success' : 'danger'}>{d.isActive ? 'Active' : 'Inactive'}</Badge>
              </td>
              <td className="px-4 py-3">
                <ActionButton
                  url={`/api/admin/dispensaries/${d.id}`}
                  method="PATCH"
                  body={{ isActive: !d.isActive }}
                  confirm={`${d.isActive ? 'Deactivate' : 'Activate'} ${d.name}?`}
                  variant={d.isActive ? 'danger' : 'success'}
                  size="sm"
                >
                  {d.isActive ? 'Deactivate' : 'Activate'}
                </ActionButton>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default async function AdminDispensariesPage() {
  await requireAuth(['SUPER_ADMIN']);
  return (
    <div>
      <PageHeader
        title="Dispensaries"
        description="Manage all registered dispensaries"
        action={
          <Link href="/admin/dispensaries/new"
            className="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
            <Plus className="h-4 w-4" /> New Dispensary
          </Link>
        }
      />
      <Suspense fallback={<div className="py-8 text-center text-sm text-gray-400">Loading…</div>}>
        <DispensaryTable />
      </Suspense>
    </div>
  );
}
