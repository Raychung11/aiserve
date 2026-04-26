'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import Link from 'next/link';

export default function NewOrganisationPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError('');
    setLoading(true);
    const fd = new FormData(e.currentTarget);
    const body = Object.fromEntries(fd.entries());
    try {
      const res = await fetch('/api/organisations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const json = await res.json();
      if (!res.ok) { setError(json.error ?? 'Failed to create organisation'); return; }
      router.push('/admin/organisations');
      router.refresh();
    } catch {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  }

  const field = (label: string, name: string, type = 'text', required = true) => (
    <div>
      <label className="mb-1 block text-sm font-medium text-gray-700">
        {label} {required && <span className="text-red-500">*</span>}
      </label>
      <input
        name={name}
        type={type}
        required={required}
        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
      />
    </div>
  );

  return (
    <div className="max-w-2xl">
      <div className="mb-4">
        <Link href="/admin/organisations" className="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
          <ArrowLeft className="h-4 w-4" /> Back
        </Link>
      </div>
      <PageHeader title="New Organisation" description="Create an organisation and its admin account" />
      <form onSubmit={handleSubmit} className="space-y-4 rounded-xl border border-gray-200 bg-white p-6">
        <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">Organisation Details</p>
        {field('Organisation Name', 'name')}
        {field('Contact Email', 'email', 'email')}
        {field('Phone', 'phone', 'tel', false)}
        <div>
          <label className="mb-1 block text-sm font-medium text-gray-700">Address</label>
          <textarea name="address" rows={2} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
        </div>
        <p className="mt-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Admin Account</p>
        {field('Admin Name', 'adminName')}
        {field('Admin Email', 'adminEmail', 'email')}
        {field('Admin Password', 'adminPassword', 'password')}
        {error && <p className="text-sm text-red-600">{error}</p>}
        <div className="flex justify-end gap-3 pt-2">
          <Link href="/admin/organisations">
            <Button variant="secondary" type="button">Cancel</Button>
          </Link>
          <Button type="submit" loading={loading}>Create Organisation</Button>
        </div>
      </form>
    </div>
  );
}
