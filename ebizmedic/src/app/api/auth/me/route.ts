import { NextResponse } from 'next/server';
import { getCurrentUser } from '@/lib/auth';
import { fail } from '@/lib/utils';

export async function GET() {
  const user = await getCurrentUser();
  if (!user) return fail('Unauthorized', 401);
  return NextResponse.json({ success: true, data: user });
}
