import { prisma } from '@/lib/db';

interface LogParams {
  userId?: string;
  action: string;
  resource: string;
  resourceId?: string;
  oldValue?: unknown;
  newValue?: unknown;
  ipAddress?: string;
  userAgent?: string;
}

export async function auditLog(params: LogParams) {
  return prisma.auditLog.create({
    data: {
      userId: params.userId,
      action: params.action,
      resource: params.resource,
      resourceId: params.resourceId,
      oldValue: params.oldValue ? (params.oldValue as object) : undefined,
      newValue: params.newValue ? (params.newValue as object) : undefined,
      ipAddress: params.ipAddress,
      userAgent: params.userAgent,
    },
  });
}

export function getIpAddress(req: Request): string {
  return (
    req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() ??
    req.headers.get('x-real-ip') ??
    'unknown'
  );
}
