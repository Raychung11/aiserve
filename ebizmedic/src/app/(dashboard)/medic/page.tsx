import { Suspense } from 'react';
import { requireAuth } from '@/lib/auth';
import { prisma } from '@/lib/db';
import { StatsCard } from '@/components/dashboard/stats-card';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Ambulance, CheckCircle, Clock, MapPin } from 'lucide-react';
import { formatDateTime } from '@/lib/utils';

export const metadata = { title: 'Mobile Medic Dashboard' };

async function MedicDashboardContent() {
  const user = await requireAuth(['MOBILE_MEDIC', 'SUPER_ADMIN']);

  const [pending, inProgress, completed] = await Promise.all([
    prisma.mobileMedicRequest.count({ where: { providerId: user.id, status: 'pending' } }),
    prisma.mobileMedicRequest.count({ where: { providerId: user.id, status: 'in_progress' } }),
    prisma.mobileMedicRequest.count({ where: { providerId: user.id, status: 'completed' } }),
  ]);

  const assignments = await prisma.mobileMedicRequest.findMany({
    where: { providerId: user.id, status: { in: ['pending', 'in_progress'] } },
    include: { patient: { include: { user: { select: { name: true, phone: true } } } } },
    orderBy: { scheduledAt: 'asc' },
    take: 6,
  });

  const statusVariant = (s: string) => {
    if (s === 'completed') return 'success';
    if (s === 'in_progress') return 'primary';
    return 'warning';
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">Mobile Medic</h2>
        <p className="text-sm text-gray-500">{user.name} — Field assignments overview</p>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatsCard title="Pending" value={pending} icon={Clock} iconBg="bg-amber-50" iconColor="text-amber-600" />
        <StatsCard title="In Progress" value={inProgress} icon={Ambulance} iconBg="bg-blue-50" iconColor="text-blue-600" />
        <StatsCard title="Completed" value={completed} icon={CheckCircle} iconBg="bg-emerald-50" iconColor="text-emerald-600" />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Active Assignments</CardTitle>
        </CardHeader>
        <CardContent>
          {assignments.length ? (
            <div className="space-y-3">
              {assignments.map((req) => (
                <div key={req.id} className="rounded-lg border border-gray-100 p-4">
                  <div className="flex items-start justify-between">
                    <div>
                      <p className="font-medium text-gray-900">{req.patient.user.name}</p>
                      <p className="text-sm text-gray-500">{req.patient.user.phone}</p>
                      <div className="mt-1.5 flex items-center gap-1 text-sm text-gray-600">
                        <MapPin className="h-3.5 w-3.5" />
                        {req.address}
                      </div>
                      {req.scheduledAt && (
                        <p className="mt-1 text-xs text-gray-500">{formatDateTime(req.scheduledAt)}</p>
                      )}
                    </div>
                    <Badge variant={statusVariant(req.status)}>
                      {req.status.replace(/_/g, ' ')}
                    </Badge>
                  </div>
                  {req.serviceType && (
                    <p className="mt-2 text-xs text-gray-500">Service: {req.serviceType}</p>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <div className="py-8 text-center">
              <Ambulance className="mx-auto h-8 w-8 text-gray-300" />
              <p className="mt-2 text-sm text-gray-500">No active assignments</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

export default async function MedicDashboard() {
  return (
    <Suspense fallback={<div className="h-96 animate-pulse rounded-xl bg-gray-100" />}>
      <MedicDashboardContent />
    </Suspense>
  );
}
