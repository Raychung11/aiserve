'use client';

import { useEffect, useState } from 'react';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { UserCircle, Save } from 'lucide-react';

interface Profile {
  name: string;
  email: string;
  phone: string | null;
  patientProfile: {
    dateOfBirth: string | null;
    gender: string | null;
    bloodGroup: string | null;
    allergies: string | null;
    medicalNotes: string | null;
    emergencyContact: string | null;
  } | null;
}

export default function PatientProfilePage() {
  const [profile, setProfile] = useState<Profile | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    fetch('/api/profile').then(r => r.json()).then(j => { setProfile(j.data); setLoading(false); });
  }, []);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setSaving(true); setError(''); setSuccess(false);
    const fd = new FormData(e.currentTarget);
    const body = {
      name: fd.get('name'),
      phone: fd.get('phone') || null,
      patient: {
        dateOfBirth: fd.get('dateOfBirth') || null,
        gender: fd.get('gender') || null,
        bloodGroup: fd.get('bloodGroup') || null,
        allergies: fd.get('allergies') || null,
        medicalNotes: fd.get('medicalNotes') || null,
        emergencyContact: fd.get('emergencyContact') || null,
      },
    };
    const res = await fetch('/api/profile', { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    const json = await res.json();
    if (!res.ok) { setError(json.error ?? 'Save failed'); setSaving(false); return; }
    setSuccess(true);
    setSaving(false);
  }

  if (loading) return <div className="py-8 text-center text-sm text-gray-400">Loading…</div>;
  if (!profile) return <p className="p-6 text-gray-500">Profile not found.</p>;

  const pp = profile.patientProfile;

  return (
    <div>
      <PageHeader title="My Profile" description="Keep your health information up to date" />

      <div className="mb-6 flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4">
        <div className="flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
          <UserCircle className="h-8 w-8 text-blue-600" />
        </div>
        <div>
          <p className="font-semibold text-gray-900">{profile.name}</p>
          <p className="text-sm text-gray-500">{profile.email}</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="rounded-xl border border-gray-200 bg-white p-5">
          <h2 className="mb-4 text-sm font-semibold text-gray-900">Personal Information</h2>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Full Name <span className="text-red-500">*</span></label>
              <input name="name" type="text" defaultValue={profile.name} required className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Phone</label>
              <input name="phone" type="tel" defaultValue={profile.phone ?? ''} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Date of Birth</label>
              <input name="dateOfBirth" type="date" defaultValue={pp?.dateOfBirth ? pp.dateOfBirth.split('T')[0] : ''} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Gender</label>
              <select name="gender" defaultValue={pp?.gender ?? ''} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none">
                <option value="">Select…</option>
                {['Male', 'Female', 'Other', 'Prefer not to say'].map(g => <option key={g} value={g}>{g}</option>)}
              </select>
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-gray-200 bg-white p-5">
          <h2 className="mb-4 text-sm font-semibold text-gray-900">Medical Information</h2>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Blood Group</label>
              <select name="bloodGroup" defaultValue={pp?.bloodGroup ?? ''} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none">
                <option value="">Select…</option>
                {['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'].map(g => <option key={g} value={g}>{g}</option>)}
              </select>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Emergency Contact</label>
              <input name="emergencyContact" type="text" defaultValue={pp?.emergencyContact ?? ''} placeholder="Name · Phone" className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
            </div>
          </div>
          <div className="mt-4">
            <label className="mb-1 block text-sm font-medium text-gray-700">Allergies</label>
            <input name="allergies" type="text" defaultValue={pp?.allergies ?? ''} placeholder="e.g. Penicillin, Shellfish" className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
          </div>
          <div className="mt-4">
            <label className="mb-1 block text-sm font-medium text-gray-700">Medical Notes</label>
            <textarea name="medicalNotes" rows={3} defaultValue={pp?.medicalNotes ?? ''} placeholder="Chronic conditions, past surgeries, etc." className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
          </div>
        </div>

        {error && <p className="text-sm text-red-600">{error}</p>}
        {success && <p className="text-sm text-emerald-600">Profile saved successfully.</p>}
        <div className="flex justify-end">
          <Button type="submit" loading={saving}><Save className="h-4 w-4" /> Save Changes</Button>
        </div>
      </form>
    </div>
  );
}
