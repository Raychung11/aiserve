import { requireAuth, AuthError } from '@/lib/auth';
import { prisma } from '@/lib/db';
import { ok, fail } from '@/lib/utils';

export async function GET() {
  try {
    const user = await requireAuth(['DISPENSARY_STAFF', 'SUPER_ADMIN']);
    const staff = await prisma.dispensaryStaff.findFirst({
      where: { userId: user.id },
      include: { dispensary: true },
    });
    if (!staff) return fail('Dispensary staff record not found', 404);
    return ok({ dispensaryId: staff.dispensaryId, dispensary: staff.dispensary });
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch dispensary', 500);
  }
}
