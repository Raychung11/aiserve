import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { updateScheduleSchema } from '@/lib/validations';
import { ok, fail } from '@/lib/utils';

export async function GET(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    await requireAuth();
    const schedules = await prisma.doctorSchedule.findMany({
      where: { doctorId: params.id, isActive: true },
      orderBy: { dayOfWeek: 'asc' },
    });
    return ok(schedules);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch schedule', 500);
  }
}

export async function PUT(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth(['DOCTOR', 'SUPER_ADMIN']);

    const doctor = await prisma.doctorProfile.findUnique({
      where: { id: params.id },
      select: { userId: true },
    });

    if (!doctor) return fail('Doctor not found', 404);
    if (user.role === 'DOCTOR' && doctor.userId !== user.id) return fail('Forbidden', 403);

    const { schedules } = updateScheduleSchema.parse(await req.json());

    await prisma.$transaction(async (tx) => {
      await tx.doctorSchedule.deleteMany({ where: { doctorId: params.id } });
      await tx.doctorSchedule.createMany({
        data: schedules.map((s) => ({ ...s, doctorId: params.id })),
      });
    });

    const updated = await prisma.doctorSchedule.findMany({
      where: { doctorId: params.id },
      orderBy: { dayOfWeek: 'asc' },
    });

    return ok(updated);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    if (err instanceof Error && err.name === 'ZodError') return fail('Invalid schedule data', 400);
    return fail('Failed to update schedule', 500);
  }
}
