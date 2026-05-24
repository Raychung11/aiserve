'use client';

import { useEffect, useState } from 'react';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Save } from 'lucide-react';

interface Org {
  id: string;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  wallet: { currency: string; lowBalanceThreshold: string | null } | null;
}

export default function OrgSettingsPage() {
  const [org, setOrg] = useState<Org | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    fetch('/api/organisations/mine')
      .then(r => r.json())
      .then(j => { setOrg(j.data); setLoading(false); })
      .catch(() => setLoading(false));
  }, []);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (!org) return;
    setSaving(true); setError(''); setSuccess(false);
    const fd = new FormData(e.currentTarget);
    const body = {
      name: fd.get('name'),
      email: fd.get('email') || null,
      phone: fd.get('phone') || null,
      address: fd.get('address') || null,
    };
    const res = await fetch(`/api/organisations/${org.id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    const json = await res.json();
    if (!res.ok) { setError(json.error ?? 'Save failed'); setSaving(false); return; }
    setOrg(prev => prev ? { ...prev, ...body } as Org : prev);
    setSuccess(true);
    setSaving(false);
  }

  if (loading) return <div className="py-8 text-center text-sm text-gray-400">Loading…</div>;
  if (!org) return <p className="p-6 text-gray-500">Organisation not found.</p>;

  return (
    <div>
      <PageHeader title="Organisation Settings" description="Update your organisation's contact and billing details" />

      <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl">
        <div className="rounded-xl border border-gray-200 bg-white p-5">
          <h2 className="mb-4 text-sm font-semibold text-gray-900">Organisation Details</h2>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
              <label className="mb-1 block text-sm font-medium text-gray-700">Organisation Name <span className="text-red-500">*</span></label>
              <input name="name" type="text" defaultValue={org.name} required className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Email</label>
              <input name="email" type="email" defaultValue={org.email ?? ''} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Phone</label>
              <input name="phone" type="tel" defaultValue={org.phone ?? ''} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
            </div>
            <div className="sm:col-span-2">
              <label className="mb-1 block text-sm font-medium text-gray-700">Address</label>
              <textarea name="address" rows={2} defaultValue={org.address ?? ''} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-gray-200 bg-white p-5">
          <h2 className="mb-3 text-sm font-semibold text-gray-900">Wallet</h2>
          <dl className="grid grid-cols-2 gap-3">
            <div>
              <dt className="text-xs text-gray-500">Currency</dt>
              <dd className="mt-0.5 text-sm font-medium text-gray-900">{org.wallet?.currency ?? 'MYR'}</dd>
            </div>
            <div>
              <dt className="text-xs text-gray-500">Low Balance Alert</dt>
              <dd className="mt-0.5 text-sm font-medium text-gray-900">
                {org.wallet?.lowBalanceThreshold ? `MYR ${org.wallet.lowBalanceThreshold}` : 'Not set'}
              </dd>
            </div>
          </dl>
          <p className="mt-3 text-xs text-gray-400">Contact your administrator to change wallet currency or alert thresholds.</p>
        </div>

        {error && <p className="text-sm text-red-600">{error}</p>}
        {success && <p className="text-sm text-emerald-600">Settings saved successfully.</p>}
        <div className="flex justify-end">
          <Button type="submit" loading={saving}><Save className="h-4 w-4" /> Save Changes</Button>
        </div>
      </form>
    </div>
  );
}
