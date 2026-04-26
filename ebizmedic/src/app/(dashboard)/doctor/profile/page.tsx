'use client';

import { useEffect, useState } from 'react';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { UserCircle, Save } from 'lucide-react';

interface Profile {
  name: string;
  email: string;
  phone: string | null;
  doctorProfile: {
    licenseNo: string | null;
    bio: string | null;
    yearsExperience: number | null;
    consultationFee: string | null;
    isVerified: boolean;
    isAvailableOnline: boolean;
    isAvailableOnsite: boolean;
    rating: string | null;
    totalReviews: number;
  } | null;
}

export default function DoctorProfilePage() {
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
      doctor: {
        licenseNo: fd.get('licenseNo') || null,
        bio: fd.get('bio') || null,
        yearsExperience: fd.get('yearsExperience') ? Number(fd.get('yearsExperience')) : null,
        consultationFee: fd.get('consultationFee') || null,
        isAvailableOnline: fd.get('isAvailableOnline') === 'true',
        isAvailableOnsite: fd.get('isAvailableOnsite') === 'true',
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

  const dp = profile.doctorProfile;

  return (
    <div>
      <PageHeader title="My Profile" description="View and update your professional information" />

      <div className="mb-6 flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4">
        <div className="flex h-14 w-14 items-center justify-center rounded-full bg-primary-100">
          <UserCircle className="h-8 w-8 text-primary-600" />
        </div>
        <div>
          <p className="font-semibold text-gray-900">{profile.name}</p>
          <p className="text-sm text-gray-500">{profile.email}</p>
          <div className="mt-1 flex gap-2">
            <Badge variant={dp?.isVerified ? 'success' : 'warning'}>{dp?.isVerified ? 'Verified' : 'Pending Verification'}</Badge>
            {dp && <span className="text-xs text-gray-400">★ {dp.rating ?? '—'} ({dp.totalReviews} reviews)</span>}
          </div>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="rounded-xl border border-gray-200 bg-white p-5">
          <h2 className="mb-4 text-sm font-semibold text-gray-900">Personal Information</h2>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {[
              { label: 'Full Name', name: 'name', defaultValue: profile.name, type: 'text', required: true },
              { label: 'Phone', name: 'phone', defaultValue: profile.phone ?? '', type: 'tel', required: false },
            ].map((f) => (
              <div key={f.name}>
                <label className="mb-1 block text-sm font-medium text-gray-700">{f.label}{f.required && <span className="text-red-500"> *</span>}</label>
                <input name={f.name} type={f.type} defaultValue={f.defaultValue} required={f.required} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-xl border border-gray-200 bg-white p-5">
          <h2 className="mb-4 text-sm font-semibold text-gray-900">Professional Details</h2>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {[
              { label: 'License No.', name: 'licenseNo', defaultValue: dp?.licenseNo ?? '', type: 'text' },
              { label: 'Years of Experience', name: 'yearsExperience', defaultValue: dp?.yearsExperience?.toString() ?? '', type: 'number' },
              { label: 'Consultation Fee (MYR)', name: 'consultationFee', defaultValue: dp?.consultationFee ?? '', type: 'number' },
            ].map((f) => (
              <div key={f.name}>
                <label className="mb-1 block text-sm font-medium text-gray-700">{f.label}</label>
                <input name={f.name} type={f.type} defaultValue={f.defaultValue} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
              </div>
            ))}
          </div>
          <div className="mt-4">
            <label className="mb-1 block text-sm font-medium text-gray-700">Bio</label>
            <textarea name="bio" rows={4} defaultValue={dp?.bio ?? ''} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
          </div>
          <div className="mt-4 grid grid-cols-2 gap-4">
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Online Consultations</label>
              <select name="isAvailableOnline" defaultValue={dp?.isAvailableOnline !== false ? 'true' : 'false'} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none">
                <option value="true">Available</option>
                <option value="false">Not Available</option>
              </select>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Onsite Consultations</label>
              <select name="isAvailableOnsite" defaultValue={dp?.isAvailableOnsite !== false ? 'true' : 'false'} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none">
                <option value="true">Available</option>
                <option value="false">Not Available</option>
              </select>
            </div>
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
