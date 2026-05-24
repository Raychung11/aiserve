import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { ActionButton } from '@/components/ui/action-button';
import { StaffAddButton } from './staff-add-button';
import { Users } from 'lucide-react';

export default async function StaffPage() {
  const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);

  const org = await prisma.organisation.findFirst({
    where: user.role === 'ORG_ADMIN'
      ? { adminUserId: user.id, deletedAt: null }
      : { deletedAt: null },
    select: { id: true, name: true },
  });

  if (!org) return <p className="p-6 text-gray-500">Organisation not found.</p>;

  const staff = await prisma.organisationStaff.findMany({
    where: { organisationId: org.id, user: { deletedAt: null } },
    include: {
      user: { select: { id: true, name: true, email: true, phone: true } },
    },
    orderBy: { joinedAt: 'desc' },
  });

  return (
    <div>
      <PageHeader
        title="Staff Members"
        description={`Managing ${org.name} — ${staff.length} member${staff.length !== 1 ? 's' : ''}`}
        action={<StaffAddButton orgId={org.id} />}
      />

      {staff.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <Users className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No staff members yet</p>
          <div className="mt-4">
            <StaffAddButton orgId={org.id} label="Add First Staff Member" />
          </div>
        </div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['Name', 'Email', 'Phone', 'Monthly Limit (MYR)', 'Eligible', ''].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {staff.map((s) => (
                <tr key={s.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-900">{s.user.name}</td>
                  <td className="px-4 py-3 text-gray-500">{s.user.email}</td>
                  <td className="px-4 py-3 text-gray-500">{s.user.phone ?? '—'}</td>
                  <td className="px-4 py-3 text-gray-500">
                    {s.monthlyLimit != null ? `MYR ${Number(s.monthlyLimit).toFixed(2)}` : 'No limit'}
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={s.isEligible ? 'success' : 'danger'}>
                      {s.isEligible ? 'Eligible' : 'Suspended'}
                    </Badge>
                  </td>
                  <td className="px-4 py-3">
                    <ActionButton
                      url={`/api/organisations/${org.id}/staff`}
                      method="PATCH"
                      body={{ staffId: s.id, isEligible: !s.isEligible }}
                      confirm={`${s.isEligible ? 'Suspend' : 'Restore'} ${s.user.name}?`}
                      variant={s.isEligible ? 'danger' : 'success'}
                      size="sm"
                    >
                      {s.isEligible ? 'Suspend' : 'Restore'}
                    </ActionButton>
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
