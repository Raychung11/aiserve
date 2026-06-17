'use client';

import { useEffect, useState } from 'react';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Save, Settings } from 'lucide-react';

interface Setting {
  id: string;
  key: string;
  value: string;
  category: string;
}

const DEFAULTS: { key: string; label: string; category: string; type?: string; hint?: string }[] = [
  { key: 'platform_name', label: 'Platform Name', category: 'general' },
  { key: 'support_email', label: 'Support Email', category: 'general', type: 'email' },
  { key: 'support_phone', label: 'Support Phone', category: 'general', type: 'tel' },
  { key: 'default_currency', label: 'Default Currency', category: 'general', hint: 'e.g. MYR' },
  { key: 'booking_advance_days', label: 'Max Booking Advance (days)', category: 'booking', type: 'number', hint: 'How far ahead patients can book' },
  { key: 'cancellation_hours', label: 'Cancellation Window (hours)', category: 'booking', type: 'number', hint: 'Min hours before appointment to allow cancellation' },
  { key: 'consultation_fee_default', label: 'Default Consultation Fee (MYR)', category: 'billing', type: 'number' },
  { key: 'wallet_topup_min', label: 'Min Wallet Top-up (MYR)', category: 'billing', type: 'number' },
  { key: 'wallet_topup_max', label: 'Max Wallet Top-up (MYR)', category: 'billing', type: 'number' },
  { key: 'maintenance_mode', label: 'Maintenance Mode', category: 'system', hint: 'Set to "true" to enable' },
  { key: 'registration_open', label: 'Open Registration', category: 'system', hint: 'Set to "true" to allow new signups' },
];

const CATEGORIES = ['general', 'booking', 'billing', 'system'];

export default function AdminSettingsPage() {
  const [settings, setSettings] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    fetch('/api/admin/settings')
      .then(r => r.json())
      .then(j => {
        const map: Record<string, string> = {};
        (j.data ?? []).forEach((s: Setting) => { map[s.key] = s.value; });
        setSettings(map);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, []);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setSaving(true); setError(''); setSuccess(false);
    const fd = new FormData(e.currentTarget);
    const updates = DEFAULTS.map(d => ({ key: d.key, value: String(fd.get(d.key) ?? '') }));
    const res = await fetch('/api/admin/settings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ settings: updates }),
    });
    const json = await res.json();
    if (!res.ok) { setError(json.error ?? 'Save failed'); setSaving(false); return; }
    setSuccess(true);
    setSaving(false);
  }

  if (loading) return <div className="py-8 text-center text-sm text-gray-400">Loading…</div>;

  return (
    <div>
      <PageHeader title="Settings" description="Configure platform-wide settings" />

      <form onSubmit={handleSubmit} className="space-y-6">
        {CATEGORIES.map((cat) => {
          const fields = DEFAULTS.filter(d => d.category === cat);
          return (
            <div key={cat} className="rounded-xl border border-gray-200 bg-white p-5">
              <h2 className="mb-4 flex items-center gap-2 text-sm font-semibold capitalize text-gray-900">
                <Settings className="h-4 w-4 text-gray-400" />
                {cat}
              </h2>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {fields.map((f) => (
                  <div key={f.key}>
                    <label className="mb-1 block text-sm font-medium text-gray-700">{f.label}</label>
                    <input
                      name={f.key}
                      type={f.type ?? 'text'}
                      defaultValue={settings[f.key] ?? ''}
                      className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                    />
                    {f.hint && <p className="mt-0.5 text-xs text-gray-400">{f.hint}</p>}
                  </div>
                ))}
              </div>
            </div>
          );
        })}

        {error && <p className="text-sm text-red-600">{error}</p>}
        {success && <p className="text-sm text-emerald-600">Settings saved successfully.</p>}
        <div className="flex justify-end">
          <Button type="submit" loading={saving}><Save className="h-4 w-4" /> Save Settings</Button>
        </div>
      </form>
    </div>
  );
}
