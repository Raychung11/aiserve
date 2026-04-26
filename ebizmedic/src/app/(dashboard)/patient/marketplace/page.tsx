'use client';

import { useEffect, useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { formatCurrency } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { SearchBar } from '@/components/ui/search-bar';
import { Modal } from '@/components/ui/modal';
import { ShoppingCart, Package, Minus, Plus, Trash2 } from 'lucide-react';
import { Suspense } from 'react';

interface Product {
  id: string;
  name: string;
  sku: string;
  price: number;
  stockQuantity: number;
  requiresPrescription: boolean;
  imageUrl?: string;
  category?: { name: string };
  dispensary?: { id: string; name: string };
}

interface CartItem {
  product: Product;
  quantity: number;
}

function ProductGrid({
  products,
  onAdd,
}: {
  products: Product[];
  onAdd: (p: Product) => void;
}) {
  if (products.length === 0) {
    return (
      <div className="py-16 text-center">
        <Package className="mx-auto h-10 w-10 text-gray-300" />
        <p className="mt-3 text-sm text-gray-500">No products found</p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      {products.map((p) => (
        <div key={p.id} className="rounded-xl border border-gray-200 bg-white p-4">
          <div className="mb-3 flex h-24 items-center justify-center rounded-lg bg-gray-50">
            {p.imageUrl ? (
              <img src={p.imageUrl} alt={p.name} className="h-full w-full rounded-lg object-cover" />
            ) : (
              <Package className="h-8 w-8 text-gray-300" />
            )}
          </div>
          <p className="text-xs text-gray-400">{p.category?.name ?? 'General'}</p>
          <p className="mt-0.5 font-semibold text-gray-900 line-clamp-2">{p.name}</p>
          <p className="mt-1 text-sm text-gray-500">{p.sku}</p>
          <p className="mt-2 text-base font-bold text-primary-600">{formatCurrency(p.price)}</p>
          <div className="mt-3 flex items-center justify-between gap-2">
            {p.requiresPrescription && (
              <Badge variant="warning" className="text-xs">Rx</Badge>
            )}
            <Button
              size="sm"
              className="ml-auto"
              disabled={p.stockQuantity === 0}
              onClick={() => onAdd(p)}
            >
              {p.stockQuantity === 0 ? 'Out of Stock' : 'Add'}
            </Button>
          </div>
        </div>
      ))}
    </div>
  );
}

export default function PatientMarketplacePage() {
  const router = useRouter();
  const [products, setProducts] = useState<Product[]>([]);
  const [cart, setCart] = useState<CartItem[]>([]);
  const [showCart, setShowCart] = useState(false);
  const [placing, setPlacing] = useState(false);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [, startTransition] = useTransition();

  useEffect(() => {
    loadProducts(search);
  }, [search]);

  async function loadProducts(q: string) {
    setLoading(true);
    const res = await fetch(`/api/marketplace/products?pageSize=48${q ? `&search=${q}` : ''}`);
    const json = await res.json();
    setProducts(json.data?.data ?? []);
    setLoading(false);
  }

  function addToCart(p: Product) {
    setCart((prev) => {
      const idx = prev.findIndex((i) => i.product.id === p.id);
      if (idx >= 0) {
        const updated = [...prev];
        updated[idx] = { ...updated[idx], quantity: updated[idx].quantity + 1 };
        return updated;
      }
      return [...prev, { product: p, quantity: 1 }];
    });
  }

  function removeFromCart(productId: string) {
    setCart((prev) => prev.filter((i) => i.product.id !== productId));
  }

  function changeQty(productId: string, delta: number) {
    setCart((prev) =>
      prev
        .map((i) => (i.product.id === productId ? { ...i, quantity: i.quantity + delta } : i))
        .filter((i) => i.quantity > 0)
    );
  }

  const total = cart.reduce((s, i) => s + i.product.price * i.quantity, 0);
  const cartCount = cart.reduce((s, i) => s + i.quantity, 0);

  const dispensaryId = cart[0]?.product.dispensary?.id;

  async function placeOrder() {
    if (!dispensaryId || cart.length === 0) return;
    setPlacing(true);
    const res = await fetch('/api/marketplace/orders', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        dispensaryId,
        items: cart.map((i) => ({ productId: i.product.id, quantity: i.quantity })),
      }),
    });
    const json = await res.json();
    if (!res.ok) { alert(json.error ?? 'Failed to place order'); setPlacing(false); return; }
    setCart([]);
    setShowCart(false);
    startTransition(() => router.push('/patient/orders'));
  }

  return (
    <div>
      <PageHeader
        title="Marketplace"
        description="Order medicines and health products"
        action={
          <button
            onClick={() => setShowCart(true)}
            className="relative inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            <ShoppingCart className="h-4 w-4" />
            Cart
            {cartCount > 0 && (
              <span className="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white">
                {cartCount}
              </span>
            )}
          </button>
        }
      />

      <div className="mb-4">
        <input
          type="search"
          placeholder="Search products…"
          className="w-full max-w-sm rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
          onChange={(e) => startTransition(() => setSearch(e.target.value))}
        />
      </div>

      {loading ? (
        <div className="py-16 text-center text-sm text-gray-400">Loading products…</div>
      ) : (
        <ProductGrid products={products} onAdd={addToCart} />
      )}

      <Modal open={showCart} onClose={() => setShowCart(false)} title={`Cart (${cartCount} items)`}>
        {cart.length === 0 ? (
          <p className="py-8 text-center text-sm text-gray-500">Your cart is empty</p>
        ) : (
          <div className="space-y-3">
            {cart.map((item) => (
              <div key={item.product.id} className="flex items-center gap-3">
                <div className="flex-1">
                  <p className="text-sm font-medium text-gray-900">{item.product.name}</p>
                  <p className="text-xs text-gray-500">{formatCurrency(item.product.price)} each</p>
                </div>
                <div className="flex items-center gap-1">
                  <button onClick={() => changeQty(item.product.id, -1)} className="rounded p-1 hover:bg-gray-100">
                    <Minus className="h-3 w-3" />
                  </button>
                  <span className="w-6 text-center text-sm">{item.quantity}</span>
                  <button onClick={() => changeQty(item.product.id, 1)} className="rounded p-1 hover:bg-gray-100">
                    <Plus className="h-3 w-3" />
                  </button>
                </div>
                <p className="w-20 text-right text-sm font-semibold text-gray-900">
                  {formatCurrency(item.product.price * item.quantity)}
                </p>
                <button onClick={() => removeFromCart(item.product.id)} className="text-red-400 hover:text-red-600">
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
            ))}
            <div className="border-t pt-3">
              <div className="flex items-center justify-between font-semibold">
                <span>Total</span>
                <span>{formatCurrency(total)}</span>
              </div>
            </div>
            <div className="flex justify-end gap-3 pt-2">
              <Button variant="secondary" onClick={() => setShowCart(false)}>Continue Shopping</Button>
              <Button onClick={placeOrder} loading={placing}>Place Order</Button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
