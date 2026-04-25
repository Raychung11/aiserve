import { NextRequest } from 'next/server';
import { requireAuth, AuthError } from '@/lib/auth';
import { bookingService } from '@/services/booking.service';
import { ok, fail } from '@/lib/utils';

export async function GET(req: NextRequest, { params }: { params: { id: string } }) {
  try {
    await requireAuth();
    const { searchParams } = new URL(req.url);
    const dateStr = searchParams.get('date');
    const duration = parseInt(searchParams.get('duration') ?? '30');

    if (!dateStr) return fail('date parameter is required', 400);

    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return fail('Invalid date', 400);

    const slots = await bookingService.getAvailableSlots(params.id, date, duration);
    return ok(slots.map((s) => s.toISOString()));
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch slots', 500);
  }
}
