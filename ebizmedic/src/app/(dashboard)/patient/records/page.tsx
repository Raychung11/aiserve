import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatDate, formatDateTime } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { FileText, Pill, ShoppingBag, Stethoscope } from 'lucide-react';

const STATUS_BADGE: Record<string, 'success' | 'warning' | 'danger' | 'secondary' | 'info'> = {
  PENDING: 'warning', CONFIRMED: 'info', COMPLETED: 'success',
  CANCELLED: 'danger', NO_SHOW: 'danger', RESERVED: 'secondary',
};

export default async function PatientRecordsPage() {
  const user = await requireAuth(['PATIENT', 'SUPER_ADMIN']);

  const patient = await prisma.patientProfile.findUnique({
    where: { userId: user.id },
    select: { id: true, bloodGroup: true, allergies: true, medicalNotes: true, emergencyContact: true },
  });
  if (!patient) return <p className="p-6 text-gray-500">Patient profile not found.</p>;

  const [appointments, prescriptions, orders] = await Promise.all([
    prisma.appointment.findMany({
      where: { patientId: patient.id, status: { in: ['COMPLETED', 'CANCELLED', 'NO_SHOW'] } },
      include: { doctor: { include: { user: { select: { name: true } } } } },
      orderBy: { scheduledAt: 'desc' },
      take: 20,
    }),
    prisma.prescription.findMany({
      where: { appointment: { patientId: patient.id } },
      include: {
        doctor: { include: { user: { select: { name: true } } } },
        items: { select: { medicationName: true, dosage: true, frequency: true, duration: true } },
      },
      orderBy: { createdAt: 'desc' },
      take: 20,
    }),
    prisma.order.findMany({
      where: { patientId: patient.id },
      include: {
        items: { include: { product: { select: { name: true } } } },
      },
      orderBy: { createdAt: 'desc' },
      take: 10,
    }),
  ]);

  return (
    <div className="space-y-8">
      <PageHeader title="Medical Records" description="Your complete health history" />

      {(patient.bloodGroup || patient.allergies || patient.medicalNotes || patient.emergencyContact) && (
        <div className="rounded-xl border border-blue-100 bg-blue-50 p-4">
          <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-blue-800">
            <FileText className="h-4 w-4" /> Health Summary
          </h2>
          <dl className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {[
              { label: 'Blood Group', value: patient.bloodGroup },
              { label: 'Allergies', value: patient.allergies },
              { label: 'Medical Notes', value: patient.medicalNotes },
              { label: 'Emergency Contact', value: patient.emergencyContact },
            ].filter(f => f.value).map(({ label, value }) => (
              <div key={label}>
                <dt className="text-xs text-blue-600">{label}</dt>
                <dd className="mt-0.5 text-sm font-medium text-blue-900">{value}</dd>
              </div>
            ))}
          </dl>
        </div>
      )}

      <section>
        <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">
          <Stethoscope className="h-4 w-4 text-primary-600" /> Past Consultations
        </h2>
        {appointments.length === 0 ? (
          <EmptyState icon={Stethoscope} text="No past consultations" />
        ) : (
          <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table className="min-w-full divide-y divide-gray-100 text-sm">
              <thead className="bg-gray-50">
                <tr>
                  {['Doctor', 'Type', 'Date', 'Status'].map(h => (
                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {appointments.map((a) => (
                  <tr key={a.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium text-gray-900">Dr. {a.doctor.user.name}</td>
                    <td className="px-4 py-3 text-gray-500">{a.type.replace(/_/g, ' ')}</td>
                    <td className="px-4 py-3 text-gray-500">{formatDateTime(a.scheduledAt)}</td>
                    <td className="px-4 py-3"><Badge variant={STATUS_BADGE[a.status] ?? 'secondary'}>{a.status}</Badge></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      <section>
        <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">
          <Pill className="h-4 w-4 text-emerald-600" /> Prescriptions
        </h2>
        {prescriptions.length === 0 ? (
          <EmptyState icon={Pill} text="No prescriptions on record" />
        ) : (
          <div className="space-y-3">
            {prescriptions.map((rx) => (
              <div key={rx.id} className="rounded-xl border border-gray-200 bg-white p-4">
                <div className="flex items-start justify-between">
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-sm font-medium text-gray-900">Dr. {rx.doctor.user.name}</span>
                      <Badge variant={rx.isDispensed ? 'success' : 'warning'}>{rx.isDispensed ? 'Dispensed' : 'Pending'}</Badge>
                    </div>
                    <ul className="mt-2 space-y-0.5">
                      {rx.items.map((item, i) => (
                        <li key={i} className="text-xs text-gray-500">
                          <span className="font-medium text-gray-700">{item.medicationName}</span>
                          {[item.dosage, item.frequency, item.duration].filter(Boolean).map((v, j) => (
                            <span key={j} className="ml-1 text-gray-400">· {v}</span>
                          ))}
                        </li>
                      ))}
                    </ul>
                  </div>
                  <span className="shrink-0 text-xs text-gray-400">{formatDate(rx.createdAt)}</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </section>

      <section>
        <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">
          <ShoppingBag className="h-4 w-4 text-amber-600" /> Marketplace Orders
        </h2>
        {orders.length === 0 ? (
          <EmptyState icon={ShoppingBag} text="No orders yet" />
        ) : (
          <div className="space-y-3">
            {orders.map((order) => (
              <div key={order.id} className="rounded-xl border border-gray-200 bg-white p-4">
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-xs font-mono text-gray-400">{order.id.slice(-8).toUpperCase()}</p>
                    <ul className="mt-1 space-y-0.5">
                      {order.items.map((item) => (
                        <li key={item.id} className="text-xs text-gray-500">
                          {item.product.name} × {item.quantity}
                        </li>
                      ))}
                    </ul>
                  </div>
                  <div className="text-right">
                    <Badge variant={order.status === 'DELIVERED' ? 'success' : order.status === 'CANCELLED' ? 'danger' : 'warning'}>
                      {order.status}
                    </Badge>
                    <p className="mt-1 text-xs text-gray-400">{formatDate(order.createdAt)}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </section>
    </div>
  );
}

function EmptyState({ icon: Icon, text }: { icon: React.ElementType; text: string }) {
  return (
    <div className="rounded-xl border border-dashed border-gray-200 py-10 text-center">
      <Icon className="mx-auto h-8 w-8 text-gray-300" />
      <p className="mt-2 text-sm text-gray-500">{text}</p>
    </div>
  );
}
