interface ZoomMeeting {
  id: string;
  uuid: string;
  host_id: string;
  topic: string;
  type: number;
  start_time: string;
  duration: number;
  password: string;
  join_url: string;
  start_url: string;
}

interface ZoomTokenCache {
  token: string;
  expiresAt: number;
}

const tokenCache = new Map<string, ZoomTokenCache>();

async function getAccessToken(accountId: string, clientId: string, clientSecret: string): Promise<string> {
  const cacheKey = `${accountId}:${clientId}`;
  const cached = tokenCache.get(cacheKey);
  if (cached && cached.expiresAt > Date.now() + 60_000) {
    return cached.token;
  }

  const credentials = Buffer.from(`${clientId}:${clientSecret}`).toString('base64');
  const res = await fetch(
    `https://zoom.us/oauth/token?grant_type=account_credentials&account_id=${accountId}`,
    {
      method: 'POST',
      headers: {
        Authorization: `Basic ${credentials}`,
        'Content-Type': 'application/x-www-form-urlencoded',
      },
    }
  );

  if (!res.ok) {
    const err = await res.text();
    throw new Error(`Zoom token error: ${err}`);
  }

  const data = (await res.json()) as { access_token: string; expires_in: number };
  tokenCache.set(cacheKey, {
    token: data.access_token,
    expiresAt: Date.now() + data.expires_in * 1000,
  });

  return data.access_token;
}

export interface CreateMeetingParams {
  hostEmail: string;
  accountId: string;
  clientId: string;
  clientSecret: string;
  topic: string;
  startTime: Date;
  durationMinutes: number;
  appointmentId: string;
}

export async function createZoomMeeting(params: CreateMeetingParams): Promise<ZoomMeeting> {
  const token = await getAccessToken(params.accountId, params.clientId, params.clientSecret);

  const res = await fetch(`https://api.zoom.us/v2/users/${params.hostEmail}/meetings`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      topic: params.topic,
      type: 2,
      start_time: params.startTime.toISOString().replace('.000Z', 'Z'),
      duration: params.durationMinutes,
      timezone: 'Asia/Kuala_Lumpur',
      settings: {
        auto_recording: 'cloud',
        waiting_room: true,
        join_before_host: false,
        mute_upon_entry: true,
        approval_type: 2,
        meeting_authentication: false,
      },
      tracking_fields: [{ field: 'appointment_id', value: params.appointmentId }],
    }),
  });

  if (!res.ok) {
    const err = await res.text();
    throw new Error(`Failed to create Zoom meeting: ${err}`);
  }

  return res.json();
}

export async function deleteZoomMeeting(
  meetingId: string,
  accountId: string,
  clientId: string,
  clientSecret: string
): Promise<void> {
  const token = await getAccessToken(accountId, clientId, clientSecret);
  await fetch(`https://api.zoom.us/v2/meetings/${meetingId}`, {
    method: 'DELETE',
    headers: { Authorization: `Bearer ${token}` },
  });
}

export function verifyZoomWebhook(
  payload: string,
  signature: string,
  timestamp: string,
  secretToken: string
): boolean {
  const crypto = require('crypto') as typeof import('crypto');
  const message = `v0:${timestamp}:${payload}`;
  const expected = `v0=${crypto.createHmac('sha256', secretToken).update(message).digest('hex')}`;
  return crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expected));
}
