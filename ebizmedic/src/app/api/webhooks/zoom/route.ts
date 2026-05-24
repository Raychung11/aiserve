import { NextRequest, NextResponse } from 'next/server';
import { prisma } from '@/lib/db';
import { verifyZoomWebhook } from '@/lib/zoom';
import { notificationService } from '@/services/notification.service';

export async function POST(req: NextRequest) {
  const signature = req.headers.get('x-zm-signature') ?? '';
  const timestamp = req.headers.get('x-zm-request-timestamp') ?? '';
  const body = await req.text();

  const secretToken = process.env.ZOOM_WEBHOOK_SECRET_TOKEN ?? '';

  // Zoom URL validation challenge
  const parsed = JSON.parse(body) as { event: string; payload: Record<string, unknown> };
  if (parsed.event === 'endpoint.url_validation') {
    const { hashForValidate, plainToken } = parsed.payload as { hashForValidate: string; plainToken: string };
    return NextResponse.json({ plainToken, encryptedToken: hashForValidate });
  }

  if (!verifyZoomWebhook(body, signature, timestamp, secretToken)) {
    return NextResponse.json({ error: 'Invalid signature' }, { status: 401 });
  }

  const { event, payload } = parsed;

  if (event === 'recording.completed') {
    const object = (payload as { object: Record<string, unknown> }).object;
    const meetingId = String(object.id);
    const recordingFiles = object.recording_files as Array<{
      id: string;
      file_type: string;
      download_url: string;
      file_size: number;
      recording_end: string;
      recording_start: string;
    }>;

    const videoFile = recordingFiles.find((f) => f.file_type === 'MP4');

    const meeting = await prisma.videoMeeting.findUnique({
      where: { meetingId },
      include: {
        appointment: {
          include: {
            patient: { include: { user: { select: { id: true } } } },
          },
        },
      },
    });

    if (meeting) {
      const durationSeconds = videoFile
        ? Math.round(
            (new Date(videoFile.recording_end).getTime() - new Date(videoFile.recording_start).getTime()) / 1000
          )
        : null;

      await prisma.consultationRecording.upsert({
        where: { videoMeetingId: meeting.id },
        create: {
          videoMeetingId: meeting.id,
          appointmentId: meeting.appointmentId,
          provider: 'zoom',
          providerFileId: videoFile?.id,
          recordingUrl: videoFile?.download_url,
          fileSize: videoFile?.file_size ? BigInt(Math.round(videoFile.file_size)) : null,
          durationSeconds,
          status: 'AVAILABLE',
          retentionDate: new Date(
            Date.now() + parseInt(process.env.RECORDING_RETENTION_DAYS ?? '90') * 86_400_000
          ),
        },
        update: {
          providerFileId: videoFile?.id,
          recordingUrl: videoFile?.download_url,
          fileSize: videoFile?.file_size ? BigInt(Math.round(videoFile.file_size)) : null,
          durationSeconds,
          status: 'AVAILABLE',
        },
      });

      await prisma.videoMeeting.update({
        where: { id: meeting.id },
        data: { recordingStatus: 'AVAILABLE' },
      });

      await notificationService.send({
        userId: meeting.appointment.patient.userId,
        title: 'Consultation Recording Ready',
        message: 'Your consultation recording is now available.',
        type: 'recording_ready',
        metadata: { appointmentId: meeting.appointmentId },
      });
    }
  }

  if (event === 'meeting.ended') {
    const object = (payload as { object: Record<string, unknown> }).object;
    const meetingId = String(object.id);

    await prisma.videoMeeting.updateMany({
      where: { meetingId },
      data: { status: 'ended' },
    });

    // Release Zoom host slot
    const meeting = await prisma.videoMeeting.findUnique({
      where: { meetingId },
      select: { zoomHostId: true },
    });

    if (meeting?.zoomHostId) {
      await prisma.zoomHost.update({
        where: { id: meeting.zoomHostId },
        data: { currentLoad: { decrement: 1 } },
      });
    }
  }

  return NextResponse.json({ success: true });
}
