'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { formatDate } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Modal } from '@/components/ui/modal';
import { PageHeader } from '@/components/ui/page-header';
import { Pill, Plus } from 'lucide-react';

interface Rx {
  id: string;
  diagnosis: string | null;
  isDispensed: boolean;
  createdAt: string;
  appointment: { bookingRef: string };
  items: { id: string; medicationName: string; dosage: string | null; frequency: string | null; duration: string | null }[];
}

export default function DoctorPrescriptionsPage() {
  const router = useRouter();
  const [prescriptions, setPrescriptions] = useState<Rx[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [appointments, setAppointments] = useState<{ id: string; bookingRef: string; patient: { user: { name: string } } }[]>([]);
  const [items, setItems] = useState([{ medicationName: '', dosage: '', frequency: '', duration: '', instructions: '' }]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    fetch('/api/prescriptions/mine').then(r => r.json()).then(j => { setPrescriptions(j.data ?? []); setLoading(false); });
    fetch('/api/bookings?status=COMPLETED').then(r => r.json()).then(j => setAppointments(j.data?.data ?? []));
  }, []);

  function addItem() {
    setItems(prev => [...prev, { medicationName: '', dosage: '', frequency: '', duration: '', instructions: '' }]);
  }
  function removeItem(i: number) {
    setItems(prev => prev.filter((_, idx) => idx !== i));
  }

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setSaving(true); setError('');
    const fd = new FormData(e.currentTarget);
    const body = {
      appointmentId: fd.get('appointmentId'),
      diagnosis: fd.get('diagnosis'),
      notes: fd.get('notes'),
      items: items.map((_, i) => ({
        medicationName: fd.get(`med_${i}`),
        dosage: fd.get(`dosage_${i}`) || undefined,
        frequency: fd.get(`freq_${i}`) || undefined,
        duration: fd.get(`dur_${i}`) || undefined,
        instructions: fd.get(`instr_${i}`) || undefined,
      })),
    };
    const res = await fetch('/api/prescriptions', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    const json = await res.json();
    if (!res.ok) { setError(json.error ?? 'Failed'); setSaving(false); return; }
    setShowModal(false);
    setPrescriptions(prev => [json.data, ...prev]);
    setSaving(false);
  }

  if (loading) return <div className="py-8 text-center text-sm text-gray-400">Loading…</div>;

  return (
    <div>
      <PageHeader
        title="Prescriptions"
        description="Issue and manage patient prescriptions"
        action={<Button onClick={() => setShowModal(true)}><Plus className="h-4 w-4" /> New Prescription</Button>}
      />

      {prescriptions.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <Pill className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No prescriptions issued yet</p>
        </div>
      ) : (
        <div className="space-y-3">
          {prescriptions.map((rx) => (
            <div key={rx.id} className="rounded-xl border border-gray-200 bg-white p-4">
              <div className="flex items-start justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-xs text-gray-400">{rx.appointment.bookingRef.slice(-8).toUpperCase()}</span>
                    <Badge variant={rx.isDispensed ? 'success' : 'warning'}>{rx.isDispensed ? 'Dispensed' : 'Pending'}</Badge>
                  </div>
                  {rx.diagnosis && <p className="mt-1 text-sm font-medium text-gray-800">Diagnosis: {rx.diagnosis}</p>}
                  <ul className="mt-2 space-y-0.5">
                    {rx.items.map((item) => (
                      <li key={item.id} className="text-xs text-gray-500">
                        <span className="font-medium">{item.medicationName}</span>
                        {item.dosage && ` · ${item.dosage}`}
                        {item.frequency && ` · ${item.frequency}`}
                        {item.duration && ` · ${item.duration}`}
                      </li>
                    ))}
                  </ul>
                </div>
                <span className="text-xs text-gray-400">{formatDate(rx.createdAt)}</span>
              </div>
            </div>
          ))}
        </div>
      )}

      <Modal open={showModal} onClose={() => setShowModal(false)} title="Issue Prescription" className="max-w-2xl">
        <form onSubmit={handleSubmit} className="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
          <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">Appointment <span className="text-red-500">*</span></label>
            <select name="appointmentId" required className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none">
              <option value="">Select completed appointment…</option>
              {appointments.map((a) => (
                <option key={a.id} value={a.id}>{a.bookingRef.slice(-8).toUpperCase()} — {a.patient.user.name}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">Diagnosis</label>
            <input name="diagnosis" className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">Notes</label>
            <textarea name="notes" rows={2} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
          </div>
          <div className="border-t pt-3">
            <div className="mb-2 flex items-center justify-between">
              <p className="text-sm font-medium text-gray-700">Medications</p>
              <Button type="button" size="sm" variant="secondary" onClick={addItem}><Plus className="h-3 w-3" /> Add</Button>
            </div>
            {items.map((_, i) => (
              <div key={i} className="mb-3 rounded-lg border border-gray-200 p-3">
                <div className="grid grid-cols-2 gap-2">
                  {[['Medication Name *', `med_${i}`, true], ['Dosage', `dosage_${i}`, false], ['Frequency', `freq_${i}`, false], ['Duration', `dur_${i}`, false]].map(([label, name, req]) => (
                    <div key={String(name)}>
                      <label className="mb-0.5 block text-xs text-gray-600">{String(label)}</label>
                      <input name={String(name)} required={Boolean(req)} className="w-full rounded border border-gray-300 px-2 py-1 text-xs focus:border-primary-500 focus:outline-none" />
                    </div>
                  ))}
                </div>
                <div className="mt-2">
                  <label className="mb-0.5 block text-xs text-gray-600">Instructions</label>
                  <input name={`instr_${i}`} className="w-full rounded border border-gray-300 px-2 py-1 text-xs focus:border-primary-500 focus:outline-none" />
                </div>
                {items.length > 1 && (
                  <button type="button" onClick={() => removeItem(i)} className="mt-2 text-xs text-red-500 hover:text-red-700">Remove</button>
                )}
              </div>
            ))}
          </div>
          {error && <p className="text-sm text-red-600">{error}</p>}
          <div className="flex justify-end gap-3 pt-2">
            <Button variant="secondary" type="button" onClick={() => setShowModal(false)}>Cancel</Button>
            <Button type="submit" loading={saving}>Issue Prescription</Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
