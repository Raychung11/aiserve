'use client';

import { useEffect, useState } from 'react';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { UserCircle, Save, Activity } from 'lucide-react';

interface Profile {
  name: string;
  email: string;
  phone: string | null;
  createdAt: string;
}

interface Stats {
  total: number;
  completed: number;
  pending: number;
}

export default function MedicProfilePage() {
  const [profile, setProfile] = useState<Profile | null>(null);
  const [stats, setStats] = useState<Stats>({ total: 0, completed: 0, pending: 0 });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    fetch('/api/profile').then(r => r.json()).then(j => {
      setProfile(j.data);
      setLoading(false);
    });
    fetch('/api/medic/requests').then(r => r.json()).then(j => {
      const data = j.data ?? [];
      setStats({
        total: data.length,
        completed: data.filter((r: { status: string }) => r.status === 'completed').length,
        pending: data.filter((r: { status: string }) => r.status === 'pending').length,
      });
    });
  }, []);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setSaving(true); setError(''); setSuccess(false);
    const fd = new FormData(e.currentTarget);
    const body = { name: fd.get('name'), phone: fd.get('phone') || null };
    const res = await fetch('/api/profile', { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    const json = await res.json();
    if (!res.ok) { setError(json.error ?? 'Save failed'); setSaving(false); return; }
    setProfile(prev => prev ? { ...prev, name: String(body.name), phone: body.phone } : prev);
    setSuccess(true);
    setSaving(false);
  }

  if (loading) return <div className="py-8 text-center text-sm text-gray-400">Loading…</div>;
  if (!profile) return <p className="p-6 text-gray-500">Profile not found.</p>;

  return (
    <div>
      <PageHeader title="My Profile" description="Manage your mobile medic account" />

      <div className="mb-6 flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4">
        <div className="flex h-14 w-14 items-center justify-center rounded-full bg-teal-100">
          <UserCircle className="h-8 w-8 text-teal-600" />
        </div>
        <div>
          <p className="font-semibold text-gray-900">{profile.name}</p>
          <p className="text-sm text-gray-500">{profile.email}</p>
        </div>
      </div>

      <div className="mb-6 grid grid-cols-3 gap-4">
        {[
          { label: 'Total Assignments', value: stats.total, color: 'bg-blue-50 text-blue-700' },
          { label: 'Completed', value: stats.completed, color: 'bg-emerald-50 text-emerald-700' },
          { label: 'Pending', value: stats.pending, color: 'bg-amber-50 text-amber-700' },
        ].map(({ label, value, color }) => (
          <div key={label} className={`rounded-xl p-4 ${color}`}>
            <Activity className="h-4 w-4 opacity-70" />
            <p className="mt-1 text-2xl font-bold">{value}</p>
            <p className="text-xs font-medium opacity-70">{label}</p>
          </div>
        ))}
      </div>

      <form onSubmit={handleSubmit} className="rounded-xl border border-gray-200 bg-white p-5">
        <h2 className="mb-4 text-sm font-semibold text-gray-900">Account Details</h2>
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
            <label className="mb-1 block text-sm font-medium text-gray-700">Email</label>
            <input type="email" value={profile.email} disabled className="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-400" />
          </div>
        </div>
        {error && <p className="mt-3 text-sm text-red-600">{error}</p>}
        {success && <p className="mt-3 text-sm text-emerald-600">Profile saved successfully.</p>}
        <div className="mt-4 flex justify-end">
          <Button type="submit" loading={saving}><Save className="h-4 w-4" /> Save Changes</Button>
        </div>
      </form>
    </div>
  );
}
