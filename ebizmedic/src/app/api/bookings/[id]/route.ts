import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { bookingService } from '@/services/booking.service';
import { notificationService } from '@/services/notification.service';
import { ok, fail } from '@/lib/utils';

export async function GET(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth();
    const appointment = await prisma.appointment.findUnique({
      where: { id: params.id },
      include: {
        patient: { include: { user: { select: { id: true, name: true, email: true, phone: true } } } },
        doctor: {
          include: {
            user: { select: { id: true, name: true, avatarUrl: true } },
            specialties: true,
          },
        },
        videoMeeting: true,
        prescription: { include: { items: true } },
        statusLogs: { orderBy: { createdAt: 'asc' } },
      },
    });

    if (!appointment) return fail('Appointment not found', 404);

    // Patients can only see their own; doctors can only see their own
    if (user.role === 'PATIENT' && appointment.patient.userId !== user.id) return fail('Forbidden', 403);
    if (user.role === 'DOCTOR' && appointment.doctor.userId !== user.id) return fail('Forbidden', 403);

    return ok(appointment);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch appointment', 500);
  }
}

export async function PATCH(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    const user = await requireAuth();
    const body = await req.json() as { action?: string; status?: string; reason?: string };

    // Support direct status mapping
    if (!body.action && body.status) {
      const map: Record<string, string> = {
        CONFIRMED: 'confirm',
        COMPLETED: 'complete',
        CANCELLED: 'cancel',
        NO_SHOW: 'no_show',
      };
      body.action = map[body.status] ?? body.status.toLowerCase();
    }

    if (body.action === 'cancel') {
      const appointment = await bookingService.cancel({
        appointmentId: params.id,
        status: 'CANCELLED',
        reason: body.reason,
        performedBy: user.id,
      });
      await notificationService.notifyBookingCancelled(params.id, body.reason);
      return ok(appointment);
    }

    if (body.action === 'confirm') {
      await requireAuth(['SUPER_ADMIN', 'DOCTOR']);
      const appointment = await bookingService.confirm({
        appointmentId: params.id,
        status: 'CONFIRMED',
        performedBy: user.id,
      });
      await notificationService.notifyBookingConfirmed(params.id);
      return ok(appointment);
    }

    if (body.action === 'complete') {
      await requireAuth(['SUPER_ADMIN', 'DOCTOR']);
      const appointment = await bookingService.complete({
        appointmentId: params.id,
        status: 'COMPLETED',
        performedBy: user.id,
      });
      return ok(appointment);
    }

    if (body.action === 'no_show') {
      await requireAuth(['SUPER_ADMIN', 'DOCTOR']);
      const appointment = await prisma.appointment.update({
        where: { id: params.id },
        data: { status: 'NO_SHOW' },
      });
      await prisma.appointmentStatusLog.create({
        data: { appointmentId: params.id, status: 'NO_SHOW', performedBy: user.id },
      });
      return ok(appointment);
    }

    return fail('Invalid action', 400);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    if (err instanceof Error) return fail(err.message, 400);
    return fail('Failed to update appointment', 500);
  }
}
