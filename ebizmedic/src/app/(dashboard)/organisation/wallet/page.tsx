import { Suspense } from 'react';
import { requireAuth } from '@/lib/auth';
import { prisma } from '@/lib/db';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDateTime } from '@/lib/utils';

export const metadata = { title: 'Wallet & Billing' };

const TX_TYPE_LABELS: Record<string, { label: string; color: string }> = {
  TOPUP: { label: 'Top-up', color: 'text-emerald-600' },
  DEDUCTION: { label: 'Deduction', color: 'text-red-600' },
  REFUND: { label: 'Refund', color: 'text-emerald-600' },
  ADJUSTMENT: { label: 'Adjustment', color: 'text-gray-600' },
  REVERSAL: { label: 'Reversal', color: 'text-amber-600' },
};

async function WalletContent() {
  const user = await requireAuth(['ORG_ADMIN', 'SUPER_ADMIN']);

  const org = await prisma.organisation.findUnique({
    where: { adminUserId: user.id },
    include: {
      wallet: {
        include: {
          transactions: {
            orderBy: { createdAt: 'desc' },
            take: 50,
          },
        },
      },
    },
  });

  if (!org?.wallet) {
    return <p className="text-sm text-gray-500">Wallet not found.</p>;
  }

  const { wallet } = org;
  const balance = Number(wallet.balance);

  const summary = wallet.transactions.reduce(
    (acc, tx) => {
      if (tx.type === 'TOPUP') acc.totalTopUp += Number(tx.amount);
      if (tx.type === 'DEDUCTION') acc.totalSpent += Number(tx.amount);
      if (tx.serviceCategory) {
        acc.byCategory[tx.serviceCategory] = (acc.byCategory[tx.serviceCategory] ?? 0) + Number(tx.amount);
      }
      return acc;
    },
    { totalTopUp: 0, totalSpent: 0, byCategory: {} as Record<string, number> }
  );

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">Wallet & Billing</h2>
        <p className="text-sm text-gray-500">{org.name}</p>
      </div>

      {/* Balance card */}
      <div className="rounded-2xl bg-gradient-to-br from-primary-600 to-primary-800 p-8 text-white shadow-lg">
        <p className="text-sm font-medium text-primary-100">Available Balance</p>
        <p className="mt-1 text-5xl font-bold">{formatCurrency(balance)}</p>
        <p className="mt-2 text-sm text-primary-200">{wallet.currency} • {org.name}</p>
        <div className="mt-6 grid grid-cols-2 gap-6 border-t border-white/20 pt-6">
          <div>
            <p className="text-xs text-primary-200">Total Topped Up</p>
            <p className="text-xl font-bold">{formatCurrency(summary.totalTopUp)}</p>
          </div>
          <div>
            <p className="text-xs text-primary-200">Total Spent</p>
            <p className="text-xl font-bold">{formatCurrency(summary.totalSpent)}</p>
          </div>
        </div>
      </div>

      {/* Spend by category */}
      {Object.keys(summary.byCategory).length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Spend by Service</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
              {Object.entries(summary.byCategory).map(([cat, amt]) => (
                <div key={cat} className="rounded-lg bg-gray-50 p-4 text-center">
                  <p className="text-lg font-bold text-gray-900">{formatCurrency(amt)}</p>
                  <p className="mt-0.5 text-xs text-gray-500">{cat.replace(/_/g, ' ')}</p>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Transaction ledger */}
      <Card>
        <CardHeader>
          <CardTitle>Transaction History</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100 text-left text-gray-500">
                  <th className="pb-3 font-medium">Date</th>
                  <th className="pb-3 font-medium">Type</th>
                  <th className="pb-3 font-medium">Service</th>
                  <th className="pb-3 font-medium">Description</th>
                  <th className="pb-3 font-medium text-right">Amount</th>
                  <th className="pb-3 font-medium text-right">Balance After</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {wallet.transactions.map((tx) => {
                  const meta = TX_TYPE_LABELS[tx.type] ?? { label: tx.type, color: 'text-gray-600' };
                  const isCredit = tx.type === 'TOPUP' || tx.type === 'REFUND';
                  return (
                    <tr key={tx.id} className="hover:bg-gray-50">
                      <td className="py-3 text-gray-600">{formatDateTime(tx.createdAt)}</td>
                      <td className="py-3">
                        <span className={`font-medium ${meta.color}`}>{meta.label}</span>
                      </td>
                      <td className="py-3 text-gray-600">
                        {tx.serviceCategory?.replace(/_/g, ' ') ?? '—'}
                      </td>
                      <td className="py-3 text-gray-600">{tx.description ?? '—'}</td>
                      <td className={`py-3 text-right font-semibold ${isCredit ? 'text-emerald-600' : 'text-red-600'}`}>
                        {isCredit ? '+' : '-'}{formatCurrency(Number(tx.amount))}
                      </td>
                      <td className="py-3 text-right text-gray-900">
                        {formatCurrency(Number(tx.balanceAfter))}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
            {!wallet.transactions.length && (
              <p className="py-6 text-center text-sm text-gray-500">No transactions yet.</p>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

export default async function WalletPage() {
  return (
    <Suspense fallback={<div className="h-96 animate-pulse rounded-xl bg-gray-100" />}>
      <WalletContent />
    </Suspense>
  );
}
