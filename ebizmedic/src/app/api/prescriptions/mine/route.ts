import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function GET() {
  const user = await requireAuth(['DOCTOR']);

  const doctor = await prisma.doctorProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
  if (!doctor) return fail('Doctor profile not found', 404);

  const prescriptions = await prisma.prescription.findMany({
    where: { doctorId: doctor.id },
    include: {
      appointment: { select: { bookingRef: true, scheduledAt: true } },
      items: { select: { id: true, medicationName: true, dosage: true, frequency: true, duration: true } },
    },
    orderBy: { createdAt: 'desc' },
    take: 50,
  });

  return ok(prescriptions);
}
