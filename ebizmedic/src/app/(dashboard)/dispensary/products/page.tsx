'use client';

import { useEffect, useState } from 'react';
import { formatCurrency } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Modal } from '@/components/ui/modal';
import { PageHeader } from '@/components/ui/page-header';
import { Package, Plus } from 'lucide-react';

interface Product {
  id: string;
  sku: string;
  name: string;
  price: number;
  stockQuantity: number;
  requiresPrescription: boolean;
  isActive: boolean;
  category?: { name: string };
}

export default function DispensaryProductsPage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [dispensaryId, setDispensaryId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [adding, setAdding] = useState(false);
  const [error, setError] = useState('');
  const [editStock, setEditStock] = useState<Record<string, string>>({});

  useEffect(() => {
    fetch('/api/auth/me')
      .then((r) => r.json())
      .then(async (me) => {
        // Get dispensary ID from staff record
        const res = await fetch(`/api/dispensary/me`);
        if (res.ok) {
          const json = await res.json();
          const id = json.data?.dispensaryId;
          if (id) {
            setDispensaryId(id);
            loadProducts(id);
          }
        }
      });
  }, []);

  async function loadProducts(id: string) {
    setLoading(true);
    const res = await fetch(`/api/marketplace/products?dispensaryId=${id}&pageSize=100`);
    const json = await res.json();
    setProducts(json.data?.data ?? []);
    setLoading(false);
  }

  async function handleAdd(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (!dispensaryId) return;
    setAdding(true);
    setError('');
    const fd = new FormData(e.currentTarget);
    const body = {
      sku: fd.get('sku'),
      name: fd.get('name'),
      description: fd.get('description') || undefined,
      price: Number(fd.get('price')),
      stockQuantity: Number(fd.get('stockQuantity')),
      requiresPrescription: fd.get('requiresPrescription') === 'on',
      dispensaryId,
    };
    const res = await fetch('/api/marketplace/products', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    const json = await res.json();
    if (!res.ok) { setError(json.error ?? 'Failed to add product'); setAdding(false); return; }
    setShowModal(false);
    loadProducts(dispensaryId);
    setAdding(false);
  }

  async function updateStock(productId: string) {
    const qty = editStock[productId];
    if (!qty) return;
    await fetch(`/api/marketplace/products/${productId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ stockQuantity: Number(qty) }),
    });
    if (dispensaryId) loadProducts(dispensaryId);
  }

  if (loading) return <div className="py-8 text-center text-sm text-gray-400">Loading…</div>;

  return (
    <div>
      <PageHeader
        title="Products"
        description="Manage your dispensary product catalogue"
        action={
          <Button onClick={() => setShowModal(true)}>
            <Plus className="h-4 w-4" /> Add Product
          </Button>
        }
      />

      {products.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
          <Package className="mx-auto h-10 w-10 text-gray-300" />
          <p className="mt-3 text-sm text-gray-500">No products yet</p>
          <Button className="mt-4" onClick={() => setShowModal(true)}>Add First Product</Button>
        </div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['SKU', 'Name', 'Category', 'Price', 'Stock', 'Rx', 'Status', ''].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {products.map((p) => (
                <tr key={p.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-mono text-xs text-gray-500">{p.sku}</td>
                  <td className="px-4 py-3 font-medium text-gray-900">{p.name}</td>
                  <td className="px-4 py-3 text-gray-500">{p.category?.name ?? '—'}</td>
                  <td className="px-4 py-3 font-medium text-gray-900">{formatCurrency(p.price)}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <input
                        type="number"
                        defaultValue={p.stockQuantity}
                        className="w-20 rounded border border-gray-300 px-2 py-1 text-sm"
                        onChange={(e) => setEditStock((prev) => ({ ...prev, [p.id]: e.target.value }))}
                      />
                      {editStock[p.id] && (
                        <Button size="sm" variant="secondary" onClick={() => updateStock(p.id)}>Save</Button>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    {p.requiresPrescription ? (
                      <Badge variant="warning">Rx</Badge>
                    ) : (
                      <span className="text-gray-400">—</span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={p.isActive ? 'success' : 'danger'}>
                      {p.isActive ? 'Active' : 'Inactive'}
                    </Badge>
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={p.stockQuantity === 0 ? 'danger' : p.stockQuantity < 10 ? 'warning' : 'success'}>
                      {p.stockQuantity === 0 ? 'Out' : p.stockQuantity < 10 ? 'Low' : 'OK'}
                    </Badge>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Modal open={showModal} onClose={() => setShowModal(false)} title="Add Product">
        <form onSubmit={handleAdd} className="space-y-4">
          {[
            { label: 'SKU', name: 'sku' },
            { label: 'Product Name', name: 'name' },
            { label: 'Price (MYR)', name: 'price', type: 'number' },
            { label: 'Initial Stock', name: 'stockQuantity', type: 'number' },
          ].map(({ label, name, type = 'text' }) => (
            <div key={name}>
              <label className="mb-1 block text-sm font-medium text-gray-700">{label} <span className="text-red-500">*</span></label>
              <input name={name} type={type} required className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
            </div>
          ))}
          <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">Description</label>
            <textarea name="description" rows={2} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
          </div>
          <div className="flex items-center gap-2">
            <input type="checkbox" name="requiresPrescription" id="rx" className="rounded" />
            <label htmlFor="rx" className="text-sm font-medium text-gray-700">Requires Prescription</label>
          </div>
          {error && <p className="text-sm text-red-600">{error}</p>}
          <div className="flex justify-end gap-3 pt-2">
            <Button variant="secondary" type="button" onClick={() => setShowModal(false)}>Cancel</Button>
            <Button type="submit" loading={adding}>Add Product</Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
