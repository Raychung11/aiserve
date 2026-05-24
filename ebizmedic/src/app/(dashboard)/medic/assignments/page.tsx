import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDateTime, formatCurrency } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { ActionButton } from '@/components/ui/action-button';
import { MapPin } from 'lucide-react';
import Link from 'next/link';

const STATUS_BADGE: Record<string, 'success' | 'warning' | 'danger' | 'secondary' | 'info'> = {
  pending: 'warning',
  assigned: 'info',
  en_route: 'info',
  completed: 'success',
  cancelled: 'danger',
};

interface Props {
  searchParams: { status?: string };
}

export default async function MedicAssignmentsPage({ searchParams }: Props) {
  const user = await requireAuth(['MOBILE_MEDIC', 'SUPER_ADMIN']);
  const active = searchParams.status ?? '';

  const where: Record<string, unknown> = {};
  if (user.role === 'MOBILE_MEDIC') {
    where.providerId = user.id;
  }
  if (active) where.status = active;

  const requests = await prisma.mobileMedicRequest.findMany({
    where,
    include: {
      patient: { include: { user: { select: { name: true, phone: true } } } },
    },
    orderBy: { createdAt: 'desc' },
    take: 50,
  });

  const statuses = ['', 'pending', 'assigned', 'en_route', 'completed', 'cancelled'];

  return (
    <div>
      <PageHeader title="Assignments" description="Mobile medic service requests assigned to you" />

      <div className="mb-4 flex flex-wrap gap-2">
        {statuses.map((s) => (
          <Link
            key={s}
            href={s ? `?status=${s}` : '?'}
            className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${
              active === s ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            {s || 'All'}
          </Link>
        ))}
      </div>

      {requests.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <MapPin className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No assignments found</p>
        </div>
      ) : (
        <div className="space-y-3">
          {requests.map((req) => (
            <div key={req.id} className="rounded-xl border border-gray-200 bg-white p-4">
              <div className="flex items-start justify-between gap-3">
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <span className="font-semibold text-gray-900">{req.patient.user.name}</span>
                    <Badge variant={STATUS_BADGE[req.status] ?? 'secondary'}>
                      {req.status.replace(/_/g, ' ')}
                    </Badge>
                  </div>
                  <p className="mt-0.5 text-sm text-gray-500">Service: {req.serviceType}</p>
                  <div className="mt-1 flex items-center gap-1 text-sm text-gray-600">
                    <MapPin className="h-3.5 w-3.5 shrink-0 text-gray-400" />
                    {req.address}
                  </div>
                  {req.scheduledAt && (
                    <p className="mt-1 text-xs text-gray-400">Scheduled: {formatDateTime(req.scheduledAt)}</p>
                  )}
                  {req.notes && <p className="mt-1 text-xs text-gray-400">{req.notes}</p>}
                  {req.amount && (
                    <p className="mt-1 text-sm font-semibold text-primary-600">{formatCurrency(Number(req.amount))}</p>
                  )}
                </div>
                <div className="flex shrink-0 flex-col gap-2">
                  {req.status === 'assigned' && (
                    <ActionButton
                      url={`/api/medic/requests/${req.id}`}
                      method="PATCH"
                      body={{ status: 'en_route' }}
                      variant="primary"
                      size="sm"
                    >
                      En Route
                    </ActionButton>
                  )}
                  {req.status === 'en_route' && (
                    <ActionButton
                      url={`/api/medic/requests/${req.id}`}
                      method="PATCH"
                      body={{ status: 'completed' }}
                      confirm="Mark this visit as completed?"
                      variant="success"
                      size="sm"
                    >
                      Complete
                    </ActionButton>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
