import { cookies } from 'next/headers';
import { prisma } from './db';
import { verifyToken } from './jwt';
import type { UserRole } from '@/types';

export interface SessionUser {
  id: string;
  email: string;
  name: string;
  role: UserRole;
  avatarUrl: string | null;
}

export async function getCurrentUser(): Promise<SessionUser | null> {
  try {
    const cookieStore = await cookies();
    const token = cookieStore.get('auth_token')?.value;
    if (!token) return null;

    const payload = await verifyToken(token);

    const user = await prisma.user.findUnique({
      where: { id: payload.id, isActive: true, deletedAt: null },
      select: { id: true, email: true, name: true, role: true, avatarUrl: true },
    });

    return user as SessionUser | null;
  } catch {
    return null;
  }
}

export async function requireAuth(allowedRoles?: UserRole[]): Promise<SessionUser> {
  const user = await getCurrentUser();
  if (!user) {
    throw new AuthError('Unauthorized', 401);
  }
  if (allowedRoles && !allowedRoles.includes(user.role)) {
    throw new AuthError('Forbidden', 403);
  }
  return user;
}

export class AuthError extends Error {
  constructor(
    message: string,
    public statusCode: number
  ) {
    super(message);
    this.name = 'AuthError';
  }
}
