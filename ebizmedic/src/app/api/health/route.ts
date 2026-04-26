import { NextResponse } from 'next/server';
import { prisma } from '@/lib/db';

export async function GET() {
  try {
    await prisma.$queryRaw`SELECT 1`;
    return NextResponse.json({
      status: 'ok',
      timestamp: new Date().toISOString(),
      database: 'connected',
      version: process.env.npm_package_version ?? '0.1.0',
    });
  } catch {
    return NextResponse.json(
      { status: 'error', database: 'disconnected', timestamp: new Date().toISOString() },
      { status: 503 }
    );
  }
}
