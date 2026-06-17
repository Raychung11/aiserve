import { SignJWT, jwtVerify } from 'jose';
import type { TokenPayload } from '@/types';

const secret = new TextEncoder().encode(
  process.env.JWT_SECRET ?? 'change-this-secret-in-production-min-32-chars'
);

export async function signToken(payload: Omit<TokenPayload, 'iat' | 'exp'>): Promise<string> {
  return new SignJWT(payload as Record<string, unknown>)
    .setProtectedHeader({ alg: 'HS256' })
    .setIssuedAt()
    .setExpirationTime(process.env.JWT_EXPIRY ?? '7d')
    .sign(secret);
}

export async function verifyToken(token: string): Promise<TokenPayload> {
  const { payload } = await jwtVerify(token, secret);
  return payload as unknown as TokenPayload;
}
