import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { createBookingSchema } from '@/lib/validations';
import { bookingService } from '@/services/booking.service';
import { notificationService } from '@/services/notification.service';
import { ok, fail } from '@/lib/utils';

export async function GET(req: NextRequest) {
  try {
    const user = await requireAuth();
    const { searchParams } = new URL(req.url);
    const page = parseInt(searchParams.get('page') ?? '1');
    const pageSize = parseInt(searchParams.get('pageSize') ?? '20');
    const status = searchParams.get('status');
    const type = searchParams.get('type');

    const where: Record<string, unknown> = {};

    if (user.role === 'PATIENT') {
      const patient = await prisma.patientProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
      if (!patient) return fail('Patient profile not found', 404);
      where.patientId = patient.id;
    } else if (user.role === 'DOCTOR') {
      const doctor = await prisma.doctorProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
      if (!doctor) return fail('Doctor profile not found', 404);
      where.doctorId = doctor.id;
    }

    if (status) where.status = status;
    if (type) where.type = type;

    const [appointments, total] = await Promise.all([
      prisma.appointment.findMany({
        where,
        include: {
          patient: { include: { user: { select: { id: true, name: true, avatarUrl: true } } } },
          doctor: {
            include: {
              user: { select: { id: true, name: true, avatarUrl: true } },
              specialties: { where: { isPrimary: true }, take: 1 },
            },
          },
          videoMeeting: { select: { joinUrl: true, password: true, status: true } },
        },
        orderBy: { scheduledAt: 'desc' },
        skip: (page - 1) * pageSize,
        take: pageSize,
      }),
      prisma.appointment.count({ where }),
    ]);

    return ok({ data: appointments, total, page, pageSize, totalPages: Math.ceil(total / pageSize) });
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch bookings', 500);
  }
}

export async function POST(req: NextRequest) {
  try {
    const user = await requireAuth(['PATIENT', 'SUPER_ADMIN']);
    const body = await req.json();
    const data = createBookingSchema.parse(body);

    let patientId: string;
    if (user.role === 'PATIENT') {
      const patient = await prisma.patientProfile.findUnique({ where: { userId: user.id }, select: { id: true } });
      if (!patient) return fail('Patient profile not found', 404);
      patientId = patient.id;
    } else {
      patientId = body.patientId as string;
      if (!patientId) return fail('patientId is required', 400);
    }

    const appointment = await bookingService.createBooking({
      patientId,
      doctorId: data.doctorId,
      type: data.type,
      scheduledAt: new Date(data.scheduledAt),
      durationMinutes: data.durationMinutes,
      notes: data.notes,
      performedBy: user.id,
    });

    await notificationService.notifyBookingConfirmed(appointment.id);

    return ok(appointment, 201);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    if (err instanceof Error) return fail(err.message, 400);
    return fail('Failed to create booking', 500);
  }
}
