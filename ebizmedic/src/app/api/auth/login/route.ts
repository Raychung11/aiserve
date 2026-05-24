import { NextRequest, NextResponse } from 'next/server';
import bcrypt from 'bcryptjs';
import { prisma } from '@/lib/db';
import { signToken } from '@/lib/jwt';
import { loginSchema } from '@/lib/validations';
import { fail } from '@/lib/utils';
import { rateLimit } from '@/lib/rate-limit';
import { getDashboardPath } from '@/types/roles';
import type { UserRole } from '@/types';

export async function POST(req: NextRequest) {
  try {
    // 10 login attempts per 15 minutes per IP
    const ip = req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() ?? 'unknown';
    const limit = rateLimit(`login:${ip}`, { windowMs: 15 * 60 * 1000, max: 10 });
    if (!limit.allowed) {
      return fail('Too many login attempts. Please try again in 15 minutes.', 429);
    }

    const body = await req.json();
    const { email, password } = loginSchema.parse(body);

    const user = await prisma.user.findUnique({
      where: { email, isActive: true, deletedAt: null },
      select: { id: true, email: true, name: true, role: true, passwordHash: true, avatarUrl: true },
    });

    if (!user) return fail('Invalid email or password', 401);

    const valid = await bcrypt.compare(password, user.passwordHash);
    if (!valid) return fail('Invalid email or password', 401);

    await prisma.user.update({
      where: { id: user.id },
      data: { lastLoginAt: new Date() },
    });

    const token = await signToken({
      id: user.id,
      email: user.email,
      name: user.name,
      role: user.role as UserRole,
    });

    const response = NextResponse.json({
      success: true,
      data: {
        user: { id: user.id, email: user.email, name: user.name, role: user.role, avatarUrl: user.avatarUrl },
        redirectTo: getDashboardPath(user.role as UserRole),
      },
    });

    response.cookies.set('auth_token', token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'lax',
      maxAge: 60 * 60 * 24 * 7, // 7 days
      path: '/',
    });

    return response;
  } catch (err) {
    if (err instanceof Error && err.name === 'ZodError') {
      return fail('Invalid request data', 400);
    }
    console.error('[AUTH LOGIN]', err);
    return fail('Authentication failed', 500);
  }
}
