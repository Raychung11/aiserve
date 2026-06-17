import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDate } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { Pill } from 'lucide-react';

export default async function PatientPrescriptionsPage() {
  const user = await requireAuth(['PATIENT', 'SUPER_ADMIN']);
  const patient = await prisma.patientProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
  if (!patient) return <p className="p-6 text-gray-500">Patient profile not found.</p>;

  const prescriptions = await prisma.prescription.findMany({
    where: { appointment: { patientId: patient.id } },
    include: {
      doctor: { include: { user: { select: { name: true } } } },
      appointment: { select: { bookingRef: true, scheduledAt: true } },
      items: true,
    },
    orderBy: { createdAt: 'desc' },
    take: 30,
  });

  return (
    <div>
      <PageHeader title="My Prescriptions" description="Prescriptions issued by your doctors" />

      {prescriptions.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <Pill className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No prescriptions yet</p>
        </div>
      ) : (
        <div className="space-y-3">
          {prescriptions.map((rx) => (
            <div key={rx.id} className="rounded-xl border border-gray-200 bg-white p-4">
              <div className="flex items-start justify-between gap-3">
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <span className="font-semibold text-gray-900">Dr. {rx.doctor.user.name}</span>
                    <Badge variant={rx.isDispensed ? 'success' : 'warning'}>
                      {rx.isDispensed ? 'Dispensed' : 'Pending'}
                    </Badge>
                  </div>
                  <p className="text-xs text-gray-400">Consultation: {formatDate(rx.appointment.scheduledAt)}</p>
                  {rx.diagnosis && (
                    <p className="mt-1 text-sm text-gray-700"><span className="font-medium">Diagnosis:</span> {rx.diagnosis}</p>
                  )}
                  <div className="mt-2 space-y-1">
                    {rx.items.map((item) => (
                      <div key={item.id} className="rounded-lg bg-gray-50 px-3 py-2">
                        <p className="text-sm font-medium text-gray-900">{item.medicationName}</p>
                        <p className="text-xs text-gray-500">
                          {[item.dosage, item.frequency, item.duration].filter(Boolean).join(' · ')}
                        </p>
                        {item.instructions && <p className="text-xs text-gray-400 italic">{item.instructions}</p>}
                      </div>
                    ))}
                  </div>
                  {rx.notes && <p className="mt-2 text-xs text-gray-500">Note: {rx.notes}</p>}
                </div>
                <span className="shrink-0 text-xs text-gray-400">{formatDate(rx.createdAt)}</span>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
