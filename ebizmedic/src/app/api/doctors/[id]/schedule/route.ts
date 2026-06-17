import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { updateScheduleSchema } from '@/lib/validations';
import { ok, fail } from '@/lib/utils';

async function resolveDoctor(id: string) {
  // Accept either a DoctorProfile.id or a User.id
  return prisma.doctorProfile.findFirst({
    where: { OR: [{ id }, { userId: id }] },
    select: { id: true, userId: true },
  });
}

export async function GET(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    await requireAuth();
    const doctor = await resolveDoctor(params.id);
    if (!doctor) return ok([]);
    const schedules = await prisma.doctorSchedule.findMany({
      where: { doctorId: doctor.id },
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

    const doctor = await resolveDoctor(params.id);

    if (!doctor) return fail('Doctor not found', 404);
    if (user.role === 'DOCTOR' && doctor.userId !== user.id) return fail('Forbidden', 403);

    const { schedules } = updateScheduleSchema.parse(await req.json());

    await prisma.$transaction(async (tx) => {
      await tx.doctorSchedule.deleteMany({ where: { doctorId: doctor.id } });
      await tx.doctorSchedule.createMany({
        data: schedules.map((s) => ({ ...s, doctorId: doctor.id })),
      });
    });

    const updated = await prisma.doctorSchedule.findMany({
      where: { doctorId: doctor.id },
      orderBy: { dayOfWeek: 'asc' },
    });

    return ok(updated);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    if (err instanceof Error && err.name === 'ZodError') return fail('Invalid schedule data', 400);
    return fail('Failed to update schedule', 500);
  }
}
