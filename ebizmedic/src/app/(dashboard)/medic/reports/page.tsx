import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatCurrency, formatDate } from '@/lib/utils';
import { PageHeader } from '@/components/ui/page-header';
import { Badge } from '@/components/ui/badge';
import { FileText } from 'lucide-react';

const STATUS_COLOUR: Record<string, string> = {
  pending: 'warning',
  en_route: 'info',
  completed: 'success',
  cancelled: 'danger',
};

export default async function MedicReportsPage() {
  const user = await requireAuth(['MOBILE_MEDIC', 'SUPER_ADMIN']);

  const requests = await prisma.mobileMedicRequest.findMany({
    where: { providerId: user.id },
    include: { patient: { include: { user: { select: { name: true } } } } },
    orderBy: { createdAt: 'desc' },
    take: 100,
  });

  const total = requests.length;
  const completed = requests.filter(r => r.status === 'completed').length;
  const pending = requests.filter(r => r.status === 'pending').length;
  const totalEarnings = requests
    .filter(r => r.status === 'completed')
    .reduce((sum, r) => sum + Number(r.amount ?? 0), 0);

  const cards = [
    { label: 'Total Assignments', value: total, colour: 'bg-blue-50 text-blue-700' },
    { label: 'Completed', value: completed, colour: 'bg-emerald-50 text-emerald-700' },
    { label: 'Pending', value: pending, colour: 'bg-amber-50 text-amber-700' },
    { label: 'Total Earnings', value: formatCurrency(totalEarnings), colour: 'bg-purple-50 text-purple-700' },
  ];

  return (
    <div>
      <PageHeader title="My Reports" description="Assignment history and earnings summary" />

      <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        {cards.map((c) => (
          <div key={c.label} className={`rounded-xl p-4 ${c.colour}`}>
            <p className="text-xs font-medium opacity-70">{c.label}</p>
            <p className="mt-1 text-2xl font-bold">{c.value}</p>
          </div>
        ))}
      </div>

      {requests.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <FileText className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No assignments yet</p>
        </div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['Patient', 'Service', 'Address', 'Scheduled', 'Amount', 'Status', 'Date'].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {requests.map((r) => (
                <tr key={r.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-900">{r.patient.user.name}</td>
                  <td className="px-4 py-3 text-gray-500">{r.serviceType}</td>
                  <td className="px-4 py-3 max-w-xs truncate text-gray-500">{r.address}</td>
                  <td className="px-4 py-3 text-gray-500">{r.scheduledAt ? formatDate(r.scheduledAt) : '—'}</td>
                  <td className="px-4 py-3 font-medium text-gray-900">{r.amount ? formatCurrency(Number(r.amount)) : '—'}</td>
                  <td className="px-4 py-3">
                    <Badge variant={(STATUS_COLOUR[r.status] as 'warning' | 'info' | 'success' | 'danger') ?? 'secondary'}>
                      {r.status.replace(/_/g, ' ')}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-gray-500">{formatDate(r.createdAt)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
