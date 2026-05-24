'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/button';
import { Select, Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { formatDateTime } from '@/lib/utils';
import { Video, MapPin, Star } from 'lucide-react';

interface Doctor {
  id: string;
  consultationFee: string | null;
  isAvailableOnline: boolean;
  isAvailableOnsite: boolean;
  rating: string | null;
  user: { name: string; avatarUrl: string | null };
  specialties: Array<{ specialty: string; isPrimary: boolean }>;
}

const APPOINTMENT_TYPES = [
  { value: 'VISUAL_CONSULTATION', label: 'Video Consultation' },
  { value: 'ONSITE_APPOINTMENT', label: 'Onsite Appointment' },
];

export default function BookPage() {
  const router = useRouter();
  const [doctors, setDoctors] = useState<Doctor[]>([]);
  const [selectedDoctor, setSelectedDoctor] = useState<Doctor | null>(null);
  const [appointmentType, setAppointmentType] = useState('VISUAL_CONSULTATION');
  const [date, setDate] = useState('');
  const [slots, setSlots] = useState<string[]>([]);
  const [selectedSlot, setSelectedSlot] = useState('');
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(false);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [error, setError] = useState('');
  const [step, setStep] = useState<'doctor' | 'slot' | 'confirm'>('doctor');

  useEffect(() => {
    async function fetchDoctors() {
      const res = await fetch(`/api/doctors?type=${appointmentType === 'VISUAL_CONSULTATION' ? 'online' : 'onsite'}`);
      const data = await res.json() as { data: { data: Doctor[] } };
      setDoctors(data.data?.data ?? []);
    }
    fetchDoctors();
  }, [appointmentType]);

  async function fetchSlots() {
    if (!selectedDoctor || !date) return;
    setLoadingSlots(true);
    try {
      const res = await fetch(`/api/doctors/${selectedDoctor.id}/slots?date=${date}`);
      const data = await res.json() as { data: string[] };
      setSlots(data.data ?? []);
    } catch {
      setSlots([]);
    } finally {
      setLoadingSlots(false);
    }
  }

  useEffect(() => { if (date && selectedDoctor) fetchSlots(); }, [date, selectedDoctor]);

  async function handleBook() {
    if (!selectedDoctor || !selectedSlot) return;
    setLoading(true);
    setError('');

    try {
      const res = await fetch('/api/bookings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          doctorId: selectedDoctor.id,
          type: appointmentType,
          scheduledAt: selectedSlot,
          notes,
        }),
      });

      const data = await res.json() as { success: boolean; error?: string };
      if (!res.ok || !data.success) {
        setError(data.error ?? 'Booking failed.');
        return;
      }

      router.push('/patient/appointments');
    } catch {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">Book Appointment</h2>
        <p className="text-sm text-gray-500">Choose a doctor and time that works for you</p>
      </div>

      {/* Step 1: Choose type & doctor */}
      <Card>
        <CardHeader>
          <CardTitle>1. Select Appointment Type & Doctor</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <Select
            label="Appointment Type"
            options={APPOINTMENT_TYPES}
            value={appointmentType}
            onChange={(e) => { setAppointmentType(e.target.value); setSelectedDoctor(null); }}
          />

          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {doctors.map((doc) => (
              <button
                key={doc.id}
                onClick={() => { setSelectedDoctor(doc); setStep('slot'); }}
                className={`rounded-xl border-2 p-4 text-left transition-colors ${
                  selectedDoctor?.id === doc.id
                    ? 'border-primary-500 bg-primary-50'
                    : 'border-gray-200 hover:border-primary-300'
                }`}
              >
                <div className="flex items-start gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 font-bold text-primary-700">
                    {doc.user.name.slice(0, 2).toUpperCase()}
                  </div>
                  <div className="flex-1">
                    <p className="font-semibold text-gray-900">Dr. {doc.user.name}</p>
                    <p className="text-xs text-gray-500">
                      {doc.specialties.find((s) => s.isPrimary)?.specialty ?? 'General Practice'}
                    </p>
                    <div className="mt-1.5 flex items-center gap-2">
                      {doc.isAvailableOnline && (
                        <Badge variant="primary" className="gap-1">
                          <Video className="h-3 w-3" /> Video
                        </Badge>
                      )}
                      {doc.isAvailableOnsite && (
                        <Badge variant="default" className="gap-1">
                          <MapPin className="h-3 w-3" /> Onsite
                        </Badge>
                      )}
                      {doc.rating && (
                        <span className="flex items-center gap-0.5 text-xs text-amber-600">
                          <Star className="h-3 w-3 fill-current" />
                          {Number(doc.rating).toFixed(1)}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              </button>
            ))}
            {!doctors.length && (
              <p className="col-span-2 py-4 text-center text-sm text-gray-500">
                No doctors available for this appointment type.
              </p>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Step 2: Choose date & slot */}
      {selectedDoctor && (
        <Card>
          <CardHeader>
            <CardTitle>2. Choose Date & Time</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <Input
              label="Select Date"
              type="date"
              min={new Date().toISOString().split('T')[0]}
              value={date}
              onChange={(e) => { setDate(e.target.value); setSelectedSlot(''); }}
            />

            {loadingSlots && <p className="text-sm text-gray-500">Loading available slots…</p>}

            {slots.length > 0 && (
              <div>
                <p className="mb-2 text-sm font-medium text-gray-700">Available Slots</p>
                <div className="flex flex-wrap gap-2">
                  {slots.map((slot) => (
                    <button
                      key={slot}
                      onClick={() => setSelectedSlot(slot)}
                      className={`rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors ${
                        selectedSlot === slot
                          ? 'border-primary-500 bg-primary-600 text-white'
                          : 'border-gray-200 text-gray-700 hover:border-primary-400'
                      }`}
                    >
                      {new Date(slot).toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' })}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {date && !loadingSlots && slots.length === 0 && (
              <p className="text-sm text-gray-500">No available slots on this date.</p>
            )}
          </CardContent>
        </Card>
      )}

      {/* Step 3: Notes & confirm */}
      {selectedSlot && (
        <Card>
          <CardHeader>
            <CardTitle>3. Confirm Booking</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="rounded-lg bg-gray-50 p-4">
              <p className="font-medium text-gray-900">Dr. {selectedDoctor?.user.name}</p>
              <p className="text-sm text-gray-600">{formatDateTime(selectedSlot)}</p>
              <p className="text-sm text-gray-600">{appointmentType.replace(/_/g, ' ')}</p>
            </div>

            <Input
              label="Notes for doctor (optional)"
              placeholder="Describe your symptoms or concerns"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
            />

            {error && <p className="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700">{error}</p>}

            <Button onClick={handleBook} loading={loading} className="w-full">
              Confirm Booking
            </Button>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
