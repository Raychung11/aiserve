import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function GET(req: NextRequest) {
  const user = await requireAuth(['SUPER_ADMIN', 'DISPENSARY_STAFF']);
  const { searchParams } = new URL(req.url);
  const isDispensed = searchParams.get('isDispensed');

  const where: Record<string, unknown> = {};
  if (isDispensed === 'true') where.isDispensed = true;
  if (isDispensed === 'false') where.isDispensed = false;

  const prescriptions = await prisma.prescription.findMany({
    where,
    include: {
      doctor: { include: { user: { select: { name: true } } } },
      appointment: { include: { patient: { include: { user: { select: { name: true, phone: true } } } } } },
      items: { include: { product: { select: { name: true } } } },
    },
    orderBy: { createdAt: 'desc' },
    take: 100,
  });

  return ok(prescriptions);
}

export async function POST(req: NextRequest) {
  const user = await requireAuth(['DOCTOR']);

  const doctor = await prisma.doctorProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
  if (!doctor) return fail('Doctor profile not found', 404);

  const body = await req.json();
  const { appointmentId, diagnosis, notes, items } = body;

  if (!appointmentId) return fail('appointmentId is required', 400);
  if (!items || !Array.isArray(items) || items.length === 0) return fail('At least one medication item is required', 400);
  for (const item of items) {
    if (!item.medicationName) return fail('Each item requires medicationName', 400);
  }

  const appointment = await prisma.appointment.findUnique({
    where: { id: appointmentId },
    select: { id: true, doctorId: true, status: true, patientId: true },
  });
  if (!appointment) return fail('Appointment not found', 404);
  if (appointment.doctorId !== doctor.id) return fail('You can only prescribe for your own appointments', 403);

  const existing = await prisma.prescription.findUnique({ where: { appointmentId } });
  if (existing) return fail('A prescription already exists for this appointment', 409);

  const patient = await prisma.patientProfile.findUnique({ where: { id: appointment.patientId }, select: { userId: true } });

  const prescription = await prisma.prescription.create({
    data: {
      appointmentId,
      doctorId: doctor.id,
      patientUserId: patient?.userId ?? '',
      diagnosis: diagnosis || undefined,
      notes: notes || undefined,
      items: {
        create: items.map((item: { medicationName: string; dosage?: string; frequency?: string; duration?: string; instructions?: string; productId?: string }) => ({
          medicationName: item.medicationName,
          dosage: item.dosage || undefined,
          frequency: item.frequency || undefined,
          duration: item.duration || undefined,
          instructions: item.instructions || undefined,
          productId: item.productId || undefined,
        })),
      },
    },
    include: {
      items: true,
      appointment: { select: { bookingRef: true } },
    },
  });

  return ok(prescription);
}
