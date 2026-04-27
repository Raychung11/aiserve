import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';
import { auditLog } from '@/services/audit.service';

export async function GET(_req: NextRequest, { params }: { params: { id: string } }) {
  try {
    await requireAuth();
    const doc = await prisma.doctorProfile.findUnique({
      where: { id: params.id },
      include: {
        user: { select: { id: true, name: true, email: true, avatarUrl: true } },
        specialties: true,
      },
    });
    if (!doc) return fail('Doctor not found', 404);
    return ok(doc);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch doctor', 500);
  }
}

export async function PATCH(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['SUPER_ADMIN']);
    const body = await req.json();

    const allowedFields = ['isVerified', 'isAvailableOnline', 'isAvailableOnsite', 'consultationFee', 'bio'];
    const data: Record<string, unknown> = {};
    for (const key of allowedFields) {
      if (key in body) data[key] = body[key];
    }

    const updated = await prisma.doctorProfile.update({
      where: { id: params.id },
      data,
    });

    await auditLog({
      userId: user.id,
      action: 'UPDATE',
      resource: 'DoctorProfile',
      resourceId: params.id,
      newValue: data,
    });

    return ok(updated);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to update doctor', 500);
  }
}
