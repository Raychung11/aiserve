import { NextRequest, NextResponse } from 'next/server';
import { jwtVerify } from 'jose';
import { ROUTE_ROLE_MAP, DASHBOARD_PATHS } from '@/types/roles';
import type { UserRole } from '@/types';
import type { TokenPayload } from '@/types';

const secret = new TextEncoder().encode(
  process.env.JWT_SECRET ?? 'change-this-secret-in-production-min-32-chars'
);

const PUBLIC_PATHS = [
  '/login',
  '/register',
  '/reset-password',
  '/api/auth/login',
  '/api/auth/register',
  '/api/auth/reset-password',
  '/api/webhooks',
  '/_next',
  '/favicon.ico',
  '/fonts',
  '/images',
];

function isPublicPath(pathname: string): boolean {
  return PUBLIC_PATHS.some((p) => pathname.startsWith(p));
}

function getRouteRole(pathname: string): UserRole[] | null {
  for (const [prefix, roles] of Object.entries(ROUTE_ROLE_MAP)) {
    if (pathname.startsWith(prefix)) return roles;
  }
  return null;
}

export async function middleware(req: NextRequest) {
  const { pathname } = req.nextUrl;

  if (pathname === '/') {
    return NextResponse.redirect(new URL('/login', req.url));
  }

  if (isPublicPath(pathname)) {
    const token = req.cookies.get('auth_token')?.value;
    if (token && (pathname.startsWith('/login') || pathname.startsWith('/register'))) {
      try {
        const { payload } = await jwtVerify(token, secret);
        const p = payload as unknown as TokenPayload;
        const dest = DASHBOARD_PATHS[p.role] ?? '/login';
        return NextResponse.redirect(new URL(dest, req.url));
      } catch {
        // expired/invalid — let through to login
      }
    }
    return NextResponse.next();
  }

  const token = req.cookies.get('auth_token')?.value;

  if (!token) {
    const loginUrl = new URL('/login', req.url);
    loginUrl.searchParams.set('callbackUrl', pathname);
    return NextResponse.redirect(loginUrl);
  }

  let payload: TokenPayload;
  try {
    const result = await jwtVerify(token, secret);
    payload = result.payload as unknown as TokenPayload;
  } catch {
    const response = NextResponse.redirect(new URL('/login', req.url));
    response.cookies.delete('auth_token');
    return response;
  }

  const allowedRoles = getRouteRole(pathname);
  if (allowedRoles && !allowedRoles.includes(payload.role)) {
    return NextResponse.redirect(new URL(DASHBOARD_PATHS[payload.role] ?? '/login', req.url));
  }

  // Propagate identity to route handlers via headers
  const requestHeaders = new Headers(req.headers);
  requestHeaders.set('x-user-id', payload.id);
  requestHeaders.set('x-user-role', payload.role);
  requestHeaders.set('x-user-email', payload.email);

  return NextResponse.next({ request: { headers: requestHeaders } });
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
};
