'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/button';
import { Modal } from '@/components/ui/modal';
import { UserPlus } from 'lucide-react';

interface Props {
  orgId: string;
  label?: string;
}

export function StaffAddButton({ orgId, label }: Props) {
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const [adding, setAdding] = useState(false);
  const [error, setError] = useState('');

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setAdding(true);
    setError('');
    const fd = new FormData(e.currentTarget);
    const body = {
      name: fd.get('name'),
      email: fd.get('email'),
      password: fd.get('password'),
      phone: fd.get('phone') || undefined,
      monthlyLimit: fd.get('monthlyLimit') ? Number(fd.get('monthlyLimit')) : undefined,
    };
    const res = await fetch(`/api/organisations/${orgId}/staff`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    const json = await res.json();
    if (!res.ok) { setError(json.error ?? 'Failed to add staff'); setAdding(false); return; }
    setOpen(false);
    router.refresh();
    setAdding(false);
  }

  return (
    <>
      <Button onClick={() => setOpen(true)}>
        <UserPlus className="h-4 w-4" /> {label ?? 'Add Staff'}
      </Button>
      <Modal open={open} onClose={() => setOpen(false)} title="Add Staff Member">
        <form onSubmit={handleSubmit} className="space-y-4">
          {[
            { label: 'Full Name', name: 'name' },
            { label: 'Email', name: 'email', type: 'email' },
            { label: 'Phone', name: 'phone', required: false },
            { label: 'Temporary Password', name: 'password', type: 'password' },
            { label: 'Monthly Limit (MYR)', name: 'monthlyLimit', type: 'number', required: false },
          ].map(({ label, name, type = 'text', required = true }) => (
            <div key={name}>
              <label className="mb-1 block text-sm font-medium text-gray-700">
                {label} {required && <span className="text-red-500">*</span>}
              </label>
              <input
                name={name}
                type={type}
                required={required}
                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
              />
            </div>
          ))}
          {error && <p className="text-sm text-red-600">{error}</p>}
          <div className="flex justify-end gap-3 pt-2">
            <Button variant="secondary" type="button" onClick={() => setOpen(false)}>Cancel</Button>
            <Button type="submit" loading={adding}>Add Staff</Button>
          </div>
        </form>
      </Modal>
    </>
  );
}
