import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function GET() {
  await requireAuth(['SUPER_ADMIN']);

  const settings = await prisma.setting.findMany({ orderBy: [{ category: 'asc' }, { key: 'asc' }] });
  return ok(settings);
}

export async function POST(req: NextRequest) {
  const user = await requireAuth(['SUPER_ADMIN']);

  const body = await req.json();
  const { settings } = body as { settings: { key: string; value: string }[] };

  if (!Array.isArray(settings)) return fail('settings must be an array', 400);

  await Promise.all(
    settings
      .filter(s => s.key && s.value !== undefined)
      .map(s =>
        prisma.setting.upsert({
          where: { key: s.key },
          create: { key: s.key, value: s.value, updatedBy: user.id },
          update: { value: s.value, updatedBy: user.id },
        })
      )
  );

  const updated = await prisma.setting.findMany({ orderBy: [{ category: 'asc' }, { key: 'asc' }] });
  return ok(updated);
}
