import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';
import { auditLog } from '@/services/audit.service';

export async function PATCH(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN']);
    if (params.id === user.id) return fail('Cannot modify your own account status', 400);

    const body = await req.json() as { isActive?: boolean; role?: string };
    const data: Record<string, unknown> = {};
    if (body.isActive !== undefined) data.isActive = body.isActive;
    if (body.role) data.role = body.role;

    const updated = await prisma.user.update({ where: { id: params.id }, data });
    await auditLog({
      userId: user.id,
      action: 'UPDATE',
      resource: 'User',
      resourceId: params.id,
      newValue: data,
    });

    return ok({ id: updated.id, isActive: updated.isActive, role: updated.role });
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to update user', 500);
  }
}
