import { Suspense } from 'react';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { formatCurrency, formatDate } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { SearchBar } from '@/components/ui/search-bar';
import { ActionButton } from '@/components/ui/action-button';
import { ShoppingBag } from 'lucide-react';

interface Props {
  searchParams: { search?: string; page?: string };
}

async function ProductTable({ search, page }: { search: string; page: number }) {
  const pageSize = 25;
  const where = {
    deletedAt: null,
    ...(search
      ? { name: { contains: search, mode: 'insensitive' as const } }
      : {}),
  };

  const [products, total] = await Promise.all([
    prisma.product.findMany({
      where,
      include: {
        dispensary: { select: { name: true } },
        category: { select: { name: true } },
        _count: { select: { orderItems: true } },
      },
      orderBy: { createdAt: 'desc' },
      skip: (page - 1) * pageSize,
      take: pageSize,
    }),
    prisma.product.count({ where }),
  ]);

  if (products.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-200 py-16 text-center">
        <ShoppingBag className="mx-auto h-10 w-10 text-gray-300" />
        <p className="mt-3 text-sm text-gray-500">No products found</p>
      </div>
    );
  }

  return (
    <>
      <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50">
            <tr>
              {['Product', 'Dispensary', 'Category', 'Price', 'Stock', 'Orders', 'Added', 'Status', ''].map((h) => (
                <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {products.map((p) => (
              <tr key={p.id} className="hover:bg-gray-50">
                <td className="px-4 py-3">
                  <div className="font-medium text-gray-900">{p.name}</div>
                  <div className="font-mono text-xs text-gray-400">{p.sku}</div>
                  {p.requiresPrescription && (
                    <span className="mt-0.5 inline-block rounded bg-purple-100 px-1.5 py-0.5 text-xs text-purple-700">Rx</span>
                  )}
                </td>
                <td className="px-4 py-3 text-gray-500">{p.dispensary.name}</td>
                <td className="px-4 py-3 text-gray-500">{p.category?.name ?? '—'}</td>
                <td className="px-4 py-3 font-medium text-gray-900">{formatCurrency(Number(p.price))}</td>
                <td className="px-4 py-3">
                  <span className={p.stockQuantity < 10 ? 'font-semibold text-red-600' : 'text-gray-500'}>
                    {p.stockQuantity}
                  </span>
                </td>
                <td className="px-4 py-3 text-gray-500">{p._count.orderItems}</td>
                <td className="px-4 py-3 text-gray-500">{formatDate(p.createdAt)}</td>
                <td className="px-4 py-3">
                  <Badge variant={p.isActive ? 'success' : 'secondary'}>
                    {p.isActive ? 'Active' : 'Inactive'}
                  </Badge>
                </td>
                <td className="px-4 py-3">
                  <ActionButton
                    url={`/api/marketplace/products/${p.id}`}
                    method="PATCH"
                    body={{ isActive: !p.isActive }}
                    confirm={`${p.isActive ? 'Deactivate' : 'Activate'} "${p.name}"?`}
                    variant={p.isActive ? 'danger' : 'success'}
                    size="sm"
                  >
                    {p.isActive ? 'Deactivate' : 'Activate'}
                  </ActionButton>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="mt-4 flex items-center justify-between text-xs text-gray-500">
        <span>{(page - 1) * pageSize + 1}–{Math.min(page * pageSize, total)} of {total} products</span>
      </div>
    </>
  );
}

export default async function AdminMarketplacePage({ searchParams }: Props) {
  await requireAuth(['SUPER_ADMIN']);
  const search = searchParams.search ?? '';
  const page = Math.max(1, parseInt(searchParams.page ?? '1'));

  const [totalProducts, activeProducts, lowStock, totalOrders] = await Promise.all([
    prisma.product.count({ where: { deletedAt: null } }),
    prisma.product.count({ where: { deletedAt: null, isActive: true } }),
    prisma.product.count({ where: { deletedAt: null, isActive: true, stockQuantity: { lt: 10 } } }),
    prisma.orderItem.count(),
  ]);

  const cards = [
    { label: 'Total Products', value: totalProducts, colour: 'bg-blue-50 text-blue-700' },
    { label: 'Active', value: activeProducts, colour: 'bg-emerald-50 text-emerald-700' },
    { label: 'Low Stock (<10)', value: lowStock, colour: 'bg-red-50 text-red-700' },
    { label: 'Total Orders', value: totalOrders, colour: 'bg-amber-50 text-amber-700' },
  ];

  return (
    <div>
      <PageHeader title="Marketplace" description="All products listed across dispensaries" />

      <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        {cards.map((c) => (
          <div key={c.label} className={`rounded-xl p-4 ${c.colour}`}>
            <p className="text-xs font-medium opacity-70">{c.label}</p>
            <p className="mt-1 text-2xl font-bold">{c.value}</p>
          </div>
        ))}
      </div>

      <div className="mb-4">
        <Suspense>
          <SearchBar placeholder="Search products…" />
        </Suspense>
      </div>

      <Suspense fallback={<div className="py-8 text-center text-sm text-gray-400">Loading…</div>}>
        <ProductTable search={search} page={page} />
      </Suspense>
    </div>
  );
}
