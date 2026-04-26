'use client';

import { useEffect, useState } from 'react';
import { formatCurrency } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Modal } from '@/components/ui/modal';
import { PageHeader } from '@/components/ui/page-header';
import { ActionButton } from '@/components/ui/action-button';
import { UserPlus, Users } from 'lucide-react';

interface StaffMember {
  id: string;
  isEligible: boolean;
  monthlyLimit: number | null;
  user: { id: string; name: string; email: string; phone: string | null };
}

export default function StaffPage() {
  const [orgId, setOrgId] = useState<string | null>(null);
  const [staff, setStaff] = useState<StaffMember[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [adding, setAdding] = useState(false);
  const [error, setError] = useState('');
  const [limitMap, setLimitMap] = useState<Record<string, string>>({});

  useEffect(() => {
    fetch('/api/auth/me')
      .then((r) => r.json())
      .then(async (me) => {
        const orgRes = await fetch('/api/organisations');
        const orgJson = await orgRes.json();
        const myOrg = orgJson.data?.data?.find(
          (o: { adminUserId: string }) => o.adminUserId === me.data?.id
        );
        if (myOrg) {
          setOrgId(myOrg.id);
          fetchStaff(myOrg.id);
        }
      });
  }, []);

  async function fetchStaff(id: string) {
    setLoading(true);
    const res = await fetch(`/api/organisations/${id}/staff`);
    const json = await res.json();
    setStaff(json.data ?? []);
    setLoading(false);
  }

  async function handleAdd(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (!orgId) return;
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
    setShowModal(false);
    fetchStaff(orgId);
    setAdding(false);
  }

  async function updateLimit(s: StaffMember) {
    if (!orgId) return;
    const val = limitMap[s.id];
    await fetch(`/api/organisations/${orgId}/staff`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ staffId: s.id, monthlyLimit: Number(val) }),
    });
    fetchStaff(orgId);
  }

  if (loading) return <div className="py-8 text-center text-sm text-gray-400">Loading…</div>;

  return (
    <div>
      <PageHeader
        title="Staff Members"
        description="Manage employee healthcare eligibility and spending limits"
        action={
          <Button onClick={() => setShowModal(true)}>
            <UserPlus className="h-4 w-4" /> Add Staff
          </Button>
        }
      />

      {staff.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <Users className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No staff members yet</p>
          <Button className="mt-4" onClick={() => setShowModal(true)}>Add First Staff Member</Button>
        </div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['Name', 'Email', 'Phone', 'Monthly Limit (MYR)', 'Eligible', ''].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {staff.map((s) => (
                <tr key={s.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-900">{s.user.name}</td>
                  <td className="px-4 py-3 text-gray-500">{s.user.email}</td>
                  <td className="px-4 py-3 text-gray-500">{s.user.phone ?? '—'}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <input
                        type="number"
                        className="w-24 rounded border border-gray-300 px-2 py-1 text-sm"
                        defaultValue={s.monthlyLimit ?? ''}
                        placeholder="No limit"
                        onChange={(e) => setLimitMap((p) => ({ ...p, [s.id]: e.target.value }))}
                      />
                      {limitMap[s.id] !== undefined && (
                        <Button size="sm" variant="secondary" onClick={() => updateLimit(s)}>
                          Save
                        </Button>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={s.isEligible ? 'success' : 'danger'}>
                      {s.isEligible ? 'Eligible' : 'Suspended'}
                    </Badge>
                  </td>
                  <td className="px-4 py-3">
                    {orgId && (
                      <ActionButton
                        url={`/api/organisations/${orgId}/staff`}
                        method="PATCH"
                        body={{ staffId: s.id, isEligible: !s.isEligible }}
                        confirm={`${s.isEligible ? 'Suspend' : 'Restore'} ${s.user.name}?`}
                        variant={s.isEligible ? 'danger' : 'success'}
                        size="sm"
                        onSuccess={() => fetchStaff(orgId!)}
                      >
                        {s.isEligible ? 'Suspend' : 'Restore'}
                      </ActionButton>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Modal open={showModal} onClose={() => setShowModal(false)} title="Add Staff Member">
        <form onSubmit={handleAdd} className="space-y-4">
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
            <Button variant="secondary" type="button" onClick={() => setShowModal(false)}>Cancel</Button>
            <Button type="submit" loading={adding}>Add Staff</Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
