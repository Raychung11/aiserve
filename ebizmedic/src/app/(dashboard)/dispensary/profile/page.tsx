import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDate } from '@/lib/utils';
import { PageHeader } from '@/components/ui/page-header';
import { Badge } from '@/components/ui/badge';
import { Building2, Package, ShoppingBag, Users } from 'lucide-react';

export default async function DispensaryProfilePage() {
  const user = await requireAuth(['DISPENSARY_STAFF', 'SUPER_ADMIN']);

  const staffRecord = await prisma.dispensaryStaff.findFirst({
    where: { userId: user.id },
    include: {
      dispensary: {
        include: {
          _count: { select: { products: true, orders: true, staff: true } },
        },
      },
    },
  });

  const dispensary = staffRecord?.dispensary ?? null;

  const fullUser = await prisma.user.findUnique({
    where: { id: user.id },
    select: { name: true, email: true, phone: true, createdAt: true },
  });

  return (
    <div>
      <PageHeader title="Profile" description="Your account and dispensary information" />

      <div className="space-y-6">
        <div className="rounded-xl border border-gray-200 bg-white p-5">
          <h2 className="mb-4 text-sm font-semibold text-gray-900">My Account</h2>
          <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {[
              { label: 'Name', value: fullUser?.name },
              { label: 'Email', value: fullUser?.email },
              { label: 'Phone', value: fullUser?.phone ?? '—' },
              { label: 'Role', value: staffRecord?.role ?? 'staff' },
              { label: 'Member since', value: formatDate(fullUser?.createdAt ?? new Date()) },
            ].map(({ label, value }) => (
              <div key={label}>
                <dt className="text-xs text-gray-500">{label}</dt>
                <dd className="mt-0.5 text-sm font-medium text-gray-900">{value}</dd>
              </div>
            ))}
          </dl>
        </div>

        {dispensary ? (
          <div className="rounded-xl border border-gray-200 bg-white p-5">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="text-sm font-semibold text-gray-900">Dispensary</h2>
              <Badge variant={dispensary.isActive ? 'success' : 'danger'}>
                {dispensary.isActive ? 'Active' : 'Inactive'}
              </Badge>
            </div>
            <div className="mb-4 flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100">
                <Building2 className="h-5 w-5 text-emerald-600" />
              </div>
              <div>
                <p className="font-semibold text-gray-900">{dispensary.name}</p>
                {dispensary.address && <p className="text-xs text-gray-500">{dispensary.address}</p>}
              </div>
            </div>
            <dl className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
              {[
                { label: 'Phone', value: dispensary.phone ?? '—' },
                { label: 'Email', value: dispensary.email ?? '—' },
              ].map(({ label, value }) => (
                <div key={label}>
                  <dt className="text-xs text-gray-500">{label}</dt>
                  <dd className="mt-0.5 text-sm text-gray-700">{value}</dd>
                </div>
              ))}
            </dl>
            <div className="grid grid-cols-3 gap-3">
              {[
                { icon: Package, label: 'Products', value: dispensary._count.products, color: 'bg-blue-50 text-blue-700' },
                { icon: ShoppingBag, label: 'Orders', value: dispensary._count.orders, color: 'bg-amber-50 text-amber-700' },
                { icon: Users, label: 'Staff', value: dispensary._count.staff, color: 'bg-purple-50 text-purple-700' },
              ].map(({ icon: Icon, label, value, color }) => (
                <div key={label} className={`rounded-lg p-3 ${color}`}>
                  <Icon className="h-4 w-4 opacity-70" />
                  <p className="mt-1 text-xl font-bold">{value}</p>
                  <p className="text-xs font-medium opacity-70">{label}</p>
                </div>
              ))}
            </div>
          </div>
        ) : (
          <div className="rounded-xl border border-dashed border-gray-200 py-12 text-center">
            <Building2 className="mx-auto h-8 w-8 text-gray-300" />
            <p className="mt-2 text-sm text-gray-500">No dispensary assigned to your account</p>
          </div>
        )}
      </div>
    </div>
  );
}
