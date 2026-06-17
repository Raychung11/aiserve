import { prisma } from '@/lib/db';
import type { NotificationChannel } from '@prisma/client';

interface SendNotificationParams {
  userId: string;
  title: string;
  message: string;
  type: string;
  channel?: NotificationChannel;
  metadata?: Record<string, unknown>;
}

export class NotificationService {
  async send(params: SendNotificationParams) {
    const notification = await prisma.notification.create({
      data: {
        userId: params.userId,
        title: params.title,
        message: params.message,
        type: params.type,
        channel: params.channel ?? 'IN_APP',
        metadata: params.metadata,
      },
    });

    if (params.channel === 'EMAIL') {
      await this.sendEmail(params.userId, params.title, params.message);
    }

    return notification;
  }

  async markRead(notificationId: string, userId: string) {
    return prisma.notification.updateMany({
      where: { id: notificationId, userId },
      data: { isRead: true, readAt: new Date() },
    });
  }

  async markAllRead(userId: string) {
    return prisma.notification.updateMany({
      where: { userId, isRead: false },
      data: { isRead: true, readAt: new Date() },
    });
  }

  async getUnread(userId: string, limit = 20) {
    return prisma.notification.findMany({
      where: { userId, isRead: false },
      orderBy: { createdAt: 'desc' },
      take: limit,
    });
  }

  async notifyBookingConfirmed(appointmentId: string) {
    const appointment = await prisma.appointment.findUnique({
      where: { id: appointmentId },
      include: {
        patient: { include: { user: { select: { id: true, name: true } } } },
        doctor: { include: { user: { select: { name: true } } } },
        videoMeeting: true,
      },
    });

    if (!appointment) return;

    const joinInfo =
      appointment.videoMeeting
        ? ` Join link: ${appointment.videoMeeting.joinUrl}`
        : '';

    await this.send({
      userId: appointment.patient.userId,
      title: 'Appointment Confirmed',
      message: `Your appointment with Dr. ${appointment.doctor.user.name} has been confirmed.${joinInfo}`,
      type: 'booking_confirmed',
      metadata: { appointmentId, bookingRef: appointment.bookingRef },
    });
  }

  async notifyBookingCancelled(appointmentId: string, reason?: string) {
    const appointment = await prisma.appointment.findUnique({
      where: { id: appointmentId },
      include: {
        patient: { include: { user: { select: { id: true } } } },
        doctor: { include: { user: { select: { id: true, name: true } } } },
      },
    });

    if (!appointment) return;

    const reasonText = reason ? ` Reason: ${reason}` : '';
    await Promise.all([
      this.send({
        userId: appointment.patient.userId,
        title: 'Appointment Cancelled',
        message: `Your appointment has been cancelled.${reasonText}`,
        type: 'booking_cancelled',
        metadata: { appointmentId },
      }),
      this.send({
        userId: appointment.doctor.userId,
        title: 'Appointment Cancelled',
        message: `An appointment has been cancelled.${reasonText}`,
        type: 'booking_cancelled',
        metadata: { appointmentId },
      }),
    ]);
  }

  async notifyWalletLowBalance(organisationId: string, balance: number) {
    const org = await prisma.organisation.findUnique({
      where: { id: organisationId },
      select: { adminUserId: true, name: true },
    });
    if (!org) return;

    await this.send({
      userId: org.adminUserId,
      title: 'Low Wallet Balance',
      message: `Your organisation wallet balance is low (MYR ${balance.toFixed(2)}). Please top up to continue services.`,
      type: 'wallet_low_balance',
      metadata: { organisationId, balance },
    });
  }

  private async sendEmail(userId: string, subject: string, body: string) {
    const user = await prisma.user.findUnique({
      where: { id: userId },
      select: { email: true, name: true },
    });
    if (!user) return;

    // Email sending is handled via queue in production
    console.log(`[EMAIL] To: ${user.email} | Subject: ${subject} | Body: ${body}`);
  }
}

export const notificationService = new NotificationService();
