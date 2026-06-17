import { requireAuth, AuthError } from '@/lib/auth';
import { prisma } from '@/lib/db';
import { ok, fail } from '@/lib/utils';

export async function GET() {
  try {
    const user = await requireAuth(['SUPER_ADMIN', 'ORG_ADMIN']);

    const org = await prisma.organisation.findFirst({
      where: user.role === 'ORG_ADMIN'
        ? { adminUserId: user.id, deletedAt: null }
        : { deletedAt: null },
      select: { id: true, name: true },
    });

    if (!org) return fail('Organisation not found', 404);
    return ok(org);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch organisation', 500);
  }
}
