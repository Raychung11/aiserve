import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function PATCH(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['MOBILE_MEDIC', 'SUPER_ADMIN']);
    const body = await req.json() as { status: string; reportNotes?: string };

    const req_ = await prisma.mobileMedicRequest.findUnique({
      where: { id: params.id },
      select: { providerId: true, status: true },
    });
    if (!req_) return fail('Request not found', 404);
    if (user.role === 'MOBILE_MEDIC' && req_.providerId !== user.id) return fail('Forbidden', 403);

    const updated = await prisma.mobileMedicRequest.update({
      where: { id: params.id },
      data: {
        status: body.status,
        ...(body.status === 'completed' && { completedAt: new Date() }),
        ...(body.reportNotes && { reportNotes: body.reportNotes }),
      },
    });

    return ok(updated);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to update request', 500);
  }
}
