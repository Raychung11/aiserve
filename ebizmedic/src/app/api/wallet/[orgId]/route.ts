import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { walletTopUpSchema } from '@/lib/validations';
import { walletService } from '@/services/wallet.service';
import { ok, fail } from '@/lib/utils';
import { notificationService } from '@/services/notification.service';

export async function GET(req: NextRequest, { params }: { params: { orgId: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);

    const wallet = await prisma.organisationWallet.findUnique({
      where: { organisationId: params.orgId },
      include: { organisation: { select: { adminUserId: true, name: true } } },
    });

    if (!wallet) return fail('Wallet not found', 404);

    if (user.role === 'ORG_ADMIN' && wallet.organisation.adminUserId !== user.id) {
      return fail('Forbidden', 403);
    }

    const { searchParams } = new URL(req.url);
    const page = parseInt(searchParams.get('page') ?? '1');
    const ledger = await walletService.getLedger(wallet.id, page);

    return ok({ wallet, ledger });
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch wallet', 500);
  }
}

export async function POST(req: NextRequest, { params }: { params: { orgId: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);
    const body = await req.json();
    const { amount, description } = walletTopUpSchema.parse(body);

    const wallet = await prisma.organisationWallet.findUnique({
      where: { organisationId: params.orgId },
      include: { organisation: { select: { adminUserId: true } } },
    });

    if (!wallet) return fail('Wallet not found', 404);
    if (user.role === 'ORG_ADMIN' && wallet.organisation.adminUserId !== user.id) {
      return fail('Forbidden', 403);
    }

    const result = await walletService.topUp(wallet.id, amount, user.id, description);

    // Notify if balance was previously low
    if (wallet.lowBalanceThreshold && Number(wallet.balance) < Number(wallet.lowBalanceThreshold)) {
      await notificationService.notifyWalletLowBalance(params.orgId, Number(result.balance));
    }

    return ok(result);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    if (err instanceof Error) return fail(err.message, 400);
    return fail('Top-up failed', 500);
  }
}
