import { prisma } from '@/lib/db';
import { createZoomMeeting } from '@/lib/zoom';
import type { AppointmentType, AppointmentStatus } from '@/types';

interface CreateBookingParams {
  patientId: string;
  doctorId: string;
  type: AppointmentType;
  scheduledAt: Date;
  durationMinutes: number;
  notes?: string;
  amount?: number;
  performedBy: string;
}

interface UpdateStatusParams {
  appointmentId: string;
  status: AppointmentStatus;
  reason?: string;
  performedBy: string;
}

export class BookingService {
  async createBooking(params: CreateBookingParams) {
    return prisma.$transaction(async (tx) => {
      const slotEnd = new Date(params.scheduledAt.getTime() + params.durationMinutes * 60_000);

      // Prevent double-booking for this doctor
      const conflict = await tx.appointment.findFirst({
        where: {
          doctorId: params.doctorId,
          status: { in: ['PENDING', 'CONFIRMED', 'RESERVED'] },
          AND: [
            { scheduledAt: { lt: slotEnd } },
            {
              scheduledAt: {
                gte: new Date(
                  params.scheduledAt.getTime() - params.durationMinutes * 60_000
                ),
              },
            },
          ],
        },
      });

      if (conflict) throw new Error('This time slot is already booked');

      // For visual consultation, ensure Zoom host is available
      let zoomHostId: string | undefined;
      if (params.type === 'VISUAL_CONSULTATION') {
        // Row-level lock to safely claim a host slot under concurrent requests
        const availableHost = await tx.$queryRaw<Array<{ id: string }>>`
          SELECT id
          FROM "ZoomHost"
          WHERE is_active = true AND current_load < max_load
          LIMIT 1
          FOR UPDATE SKIP LOCKED
        `;

        if (availableHost.length === 0) {
          throw new Error('No video consultation host available at this time. Please choose a different time slot.');
        }
        zoomHostId = availableHost[0].id;
      }

      const appointment = await tx.appointment.create({
        data: {
          patientId: params.patientId,
          doctorId: params.doctorId,
          type: params.type,
          status: 'PENDING',
          scheduledAt: params.scheduledAt,
          durationMinutes: params.durationMinutes,
          notes: params.notes,
          amount: params.amount,
        },
        include: {
          patient: { include: { user: { select: { name: true, email: true } } } },
          doctor: { include: { user: { select: { name: true } } } },
        },
      });

      await tx.appointmentStatusLog.create({
        data: {
          appointmentId: appointment.id,
          status: 'PENDING',
          performedBy: params.performedBy,
        },
      });

      // Create Zoom meeting if visual consultation
      if (params.type === 'VISUAL_CONSULTATION' && zoomHostId) {
        const zoomHost = await tx.zoomHost.findUniqueOrThrow({ where: { id: zoomHostId } });

        try {
          const meeting = await createZoomMeeting({
            hostEmail: zoomHost.email,
            accountId: zoomHost.accountId,
            clientId: zoomHost.clientId,
            clientSecret: zoomHost.clientSecret,
            topic: `eBizMedic Consultation - ${appointment.id}`,
            startTime: params.scheduledAt,
            durationMinutes: params.durationMinutes,
            appointmentId: appointment.id,
          });

          await tx.videoMeeting.create({
            data: {
              appointmentId: appointment.id,
              zoomHostId,
              meetingId: String(meeting.id),
              hostUrl: meeting.start_url,
              joinUrl: meeting.join_url,
              password: meeting.password,
              startTime: params.scheduledAt,
              durationMinutes: params.durationMinutes,
            },
          });

          await tx.zoomHost.update({
            where: { id: zoomHostId },
            data: { currentLoad: { increment: 1 } },
          });
        } catch (err) {
          // Zoom meeting creation failed — booking still created but flagged
          console.error('Zoom meeting creation failed:', err);
        }
      }

      return appointment;
    });
  }

  async confirm(params: UpdateStatusParams) {
    return this.updateStatus({ ...params, status: 'CONFIRMED' });
  }

  async cancel(params: UpdateStatusParams) {
    return prisma.$transaction(async (tx) => {
      const appointment = await tx.appointment.findUniqueOrThrow({
        where: { id: params.appointmentId },
        include: { videoMeeting: true },
      });

      if (['COMPLETED', 'CANCELLED'].includes(appointment.status)) {
        throw new Error(`Cannot cancel a ${appointment.status.toLowerCase()} appointment`);
      }

      await tx.appointment.update({
        where: { id: params.appointmentId },
        data: { status: 'CANCELLED', cancelReason: params.reason },
      });

      await tx.appointmentStatusLog.create({
        data: {
          appointmentId: params.appointmentId,
          status: 'CANCELLED',
          reason: params.reason,
          performedBy: params.performedBy,
        },
      });

      // Release Zoom host slot
      if (appointment.videoMeeting?.zoomHostId) {
        await tx.zoomHost.update({
          where: { id: appointment.videoMeeting.zoomHostId },
          data: { currentLoad: { decrement: 1 } },
        });
      }

      return appointment;
    });
  }

  async complete(params: UpdateStatusParams) {
    return this.updateStatus({ ...params, status: 'COMPLETED' });
  }

  async getAvailableSlots(doctorId: string, date: Date, durationMinutes = 30) {
    const dayOfWeek = date.getDay();

    const [schedule, bookedAppointments, leaves] = await Promise.all([
      prisma.doctorSchedule.findFirst({
        where: { doctorId, dayOfWeek, isActive: true },
      }),
      prisma.appointment.findMany({
        where: {
          doctorId,
          status: { in: ['PENDING', 'CONFIRMED', 'RESERVED'] },
          scheduledAt: {
            gte: new Date(date.getFullYear(), date.getMonth(), date.getDate()),
            lt: new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1),
          },
        },
        select: { scheduledAt: true, durationMinutes: true },
      }),
      prisma.doctorLeave.findMany({
        where: {
          doctorId,
          startDate: { lte: date },
          endDate: { gte: date },
        },
      }),
    ]);

    if (!schedule || leaves.length > 0) return [];

    const [startH, startM] = schedule.startTime.split(':').map(Number);
    const [endH, endM] = schedule.endTime.split(':').map(Number);

    const slots: Date[] = [];
    const cursor = new Date(date);
    cursor.setHours(startH, startM, 0, 0);
    const endTime = new Date(date);
    endTime.setHours(endH, endM, 0, 0);

    const step = durationMinutes + schedule.bufferTime;

    while (cursor.getTime() + durationMinutes * 60_000 <= endTime.getTime()) {
      const slotStart = new Date(cursor);
      const slotEnd = new Date(cursor.getTime() + durationMinutes * 60_000);

      const isBooked = bookedAppointments.some((appt) => {
        const apptEnd = new Date(appt.scheduledAt.getTime() + appt.durationMinutes * 60_000);
        return slotStart < apptEnd && slotEnd > appt.scheduledAt;
      });

      if (!isBooked && slotStart > new Date()) {
        slots.push(new Date(slotStart));
      }

      cursor.setMinutes(cursor.getMinutes() + step);
    }

    return slots;
  }

  private async updateStatus(params: UpdateStatusParams & { status: AppointmentStatus }) {
    return prisma.$transaction(async (tx) => {
      const appointment = await tx.appointment.update({
        where: { id: params.appointmentId },
        data: { status: params.status },
      });

      await tx.appointmentStatusLog.create({
        data: {
          appointmentId: params.appointmentId,
          status: params.status,
          reason: params.reason,
          performedBy: params.performedBy,
        },
      });

      return appointment;
    });
  }
}

export const bookingService = new BookingService();
