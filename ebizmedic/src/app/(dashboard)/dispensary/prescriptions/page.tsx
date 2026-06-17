import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDate } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { ActionButton } from '@/components/ui/action-button';
import { Pill } from 'lucide-react';
import Link from 'next/link';

interface Props { searchParams: { filter?: string } }

export default async function DispensaryPrescriptionsPage({ searchParams }: Props) {
  await requireAuth(['DISPENSARY_STAFF', 'SUPER_ADMIN']);
  const filter = searchParams.filter ?? 'pending';

  const where = filter === 'pending' ? { isDispensed: false } : filter === 'dispensed' ? { isDispensed: true } : {};

  const prescriptions = await prisma.prescription.findMany({
    where,
    include: {
      doctor: { include: { user: { select: { name: true } } } },
      appointment: { include: { patient: { include: { user: { select: { name: true, phone: true } } } } } },
      items: { include: { product: { select: { name: true } } } },
    },
    orderBy: { createdAt: 'desc' },
    take: 50,
  });

  const filters = [
    { value: 'pending', label: 'Pending' },
    { value: 'dispensed', label: 'Dispensed' },
    { value: '', label: 'All' },
  ];

  return (
    <div>
      <PageHeader title="Prescriptions" description="Incoming prescriptions awaiting dispensing" />

      <div className="mb-4 flex gap-2">
        {filters.map((f) => (
          <Link key={f.value} href={f.value ? `?filter=${f.value}` : '?'}
            className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${filter === f.value ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}>
            {f.label}
          </Link>
        ))}
      </div>

      {prescriptions.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <Pill className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No prescriptions found</p>
        </div>
      ) : (
        <div className="space-y-3">
          {prescriptions.map((rx) => (
            <div key={rx.id} className="rounded-xl border border-gray-200 bg-white p-4">
              <div className="flex items-start justify-between gap-3">
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <span className="font-semibold text-gray-900">{rx.appointment.patient.user.name}</span>
                    <span className="text-gray-400">|</span>
                    <span className="text-sm text-gray-500">Dr. {rx.doctor.user.name}</span>
                    <Badge variant={rx.isDispensed ? 'success' : 'warning'}>
                      {rx.isDispensed ? 'Dispensed' : 'Pending'}
                    </Badge>
                  </div>
                  <p className="text-xs text-gray-400">{rx.appointment.patient.user.phone ?? 'No phone'} · {formatDate(rx.createdAt)}</p>
                  {rx.diagnosis && <p className="mt-1 text-sm text-gray-700">Diagnosis: {rx.diagnosis}</p>}
                  <ul className="mt-2 space-y-1">
                    {rx.items.map((item) => (
                      <li key={item.id} className="rounded bg-gray-50 px-3 py-1.5 text-xs">
                        <span className="font-medium">{item.medicationName}</span>
                        {item.product && <span className="ml-2 text-gray-400">(Product: {item.product.name})</span>}
                        <span className="ml-2 text-gray-500">{[item.dosage, item.frequency, item.duration].filter(Boolean).join(' · ')}</span>
                      </li>
                    ))}
                  </ul>
                </div>
                {!rx.isDispensed && (
                  <ActionButton
                    url={`/api/prescriptions/${rx.id}/dispense`}
                    method="PATCH"
                    body={{}}
                    confirm="Mark prescription as dispensed?"
                    variant="success"
                    size="sm"
                  >
                    Mark Dispensed
                  </ActionButton>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
