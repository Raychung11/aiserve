import { Prisma } from '@prisma/client';
import { prisma } from '@/lib/db';
import type { ServiceCategory, TransactionType } from '@/types';

type TxClient = Prisma.TransactionClient;

export class WalletService {
  async topUp(
    walletId: string,
    amount: number,
    performedBy: string,
    description?: string
  ) {
    if (amount <= 0) throw new Error('Top-up amount must be positive');

    return prisma.$transaction(async (tx) => {
      const wallet = await this.lockWallet(tx, walletId);

      const balanceBefore = wallet.balance;
      const balanceAfter = new Prisma.Decimal(wallet.balance).add(new Prisma.Decimal(amount));

      await tx.organisationWallet.update({
        where: { id: walletId },
        data: { balance: balanceAfter },
      });

      const transaction = await tx.walletTransaction.create({
        data: {
          walletId,
          type: 'TOPUP',
          amount: new Prisma.Decimal(amount),
          balanceBefore,
          balanceAfter,
          performedBy,
          description: description ?? 'Wallet top-up',
        },
      });

      return { balance: balanceAfter, transaction };
    });
  }

  async deduct(
    walletId: string,
    amount: number,
    serviceCategory: ServiceCategory,
    referenceType: string,
    referenceId: string,
    performedBy: string,
    staffUserId?: string,
    description?: string
  ) {
    if (amount <= 0) throw new Error('Deduction amount must be positive');

    return prisma.$transaction(async (tx) => {
      const wallet = await this.lockWallet(tx, walletId);

      if (!wallet.isActive) throw new Error('Wallet is inactive');

      const deductAmount = new Prisma.Decimal(amount);
      const balanceBefore = wallet.balance;

      if (new Prisma.Decimal(balanceBefore).lt(deductAmount)) {
        throw new Error('Insufficient wallet balance');
      }

      const balanceAfter = new Prisma.Decimal(balanceBefore).sub(deductAmount);

      await tx.organisationWallet.update({
        where: { id: walletId },
        data: { balance: balanceAfter },
      });

      const transaction = await tx.walletTransaction.create({
        data: {
          walletId,
          type: 'DEDUCTION',
          amount: deductAmount,
          balanceBefore,
          balanceAfter,
          serviceCategory,
          referenceType,
          referenceId,
          staffUserId,
          performedBy,
          description,
        },
      });

      return { balance: balanceAfter, transaction };
    });
  }

  async refund(
    walletId: string,
    amount: number,
    referenceTransactionId: string,
    performedBy: string,
    description?: string
  ) {
    if (amount <= 0) throw new Error('Refund amount must be positive');

    return prisma.$transaction(async (tx) => {
      const wallet = await this.lockWallet(tx, walletId);

      const balanceBefore = wallet.balance;
      const balanceAfter = new Prisma.Decimal(balanceBefore).add(new Prisma.Decimal(amount));

      await tx.organisationWallet.update({
        where: { id: walletId },
        data: { balance: balanceAfter },
      });

      const transaction = await tx.walletTransaction.create({
        data: {
          walletId,
          type: 'REFUND',
          amount: new Prisma.Decimal(amount),
          balanceBefore,
          balanceAfter,
          referenceType: 'wallet_transaction',
          referenceId: referenceTransactionId,
          performedBy,
          description: description ?? 'Refund',
        },
      });

      return { balance: balanceAfter, transaction };
    });
  }

  async adjust(
    walletId: string,
    amount: number,
    type: Extract<TransactionType, 'ADJUSTMENT' | 'REVERSAL'>,
    performedBy: string,
    description: string
  ) {
    return prisma.$transaction(async (tx) => {
      const wallet = await this.lockWallet(tx, walletId);

      const adjustAmount = new Prisma.Decimal(Math.abs(amount));
      const balanceBefore = wallet.balance;
      const balanceAfter =
        amount >= 0
          ? new Prisma.Decimal(balanceBefore).add(adjustAmount)
          : new Prisma.Decimal(balanceBefore).sub(adjustAmount);

      if (balanceAfter.lt(0)) throw new Error('Adjustment would result in negative balance');

      await tx.organisationWallet.update({
        where: { id: walletId },
        data: { balance: balanceAfter },
      });

      const transaction = await tx.walletTransaction.create({
        data: {
          walletId,
          type,
          amount: new Prisma.Decimal(amount),
          balanceBefore,
          balanceAfter,
          performedBy,
          description,
        },
      });

      return { balance: balanceAfter, transaction };
    });
  }

  async getBalance(walletId: string): Promise<Prisma.Decimal> {
    const wallet = await prisma.organisationWallet.findUniqueOrThrow({
      where: { id: walletId },
      select: { balance: true },
    });
    return wallet.balance;
  }

  async getLedger(walletId: string, page = 1, pageSize = 20) {
    const skip = (page - 1) * pageSize;
    const [transactions, total] = await Promise.all([
      prisma.walletTransaction.findMany({
        where: { walletId },
        orderBy: { createdAt: 'desc' },
        skip,
        take: pageSize,
      }),
      prisma.walletTransaction.count({ where: { walletId } }),
    ]);
    return { transactions, total, page, pageSize, totalPages: Math.ceil(total / pageSize) };
  }

  // Row-level lock to prevent race conditions
  private async lockWallet(tx: TxClient, walletId: string) {
    const wallets = await tx.$queryRaw<Array<{ id: string; balance: Prisma.Decimal; isActive: boolean }>>`
      SELECT id, balance, "isActive"
      FROM "OrganisationWallet"
      WHERE id = ${walletId}
      FOR UPDATE
    `;
    if (wallets.length === 0) throw new Error('Wallet not found');
    return wallets[0];
  }
}

export const walletService = new WalletService();
