import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function PATCH(_req: Request, { params }: { params: { id: string } }) {
  await requireAuth(['DISPENSARY_STAFF', 'SUPER_ADMIN']);

  const prescription = await prisma.prescription.findUnique({ where: { id: params.id } });
  if (!prescription) return fail('Prescription not found', 404);
  if (prescription.isDispensed) return fail('Already dispensed', 409);

  const updated = await prisma.prescription.update({
    where: { id: params.id },
    data: { isDispensed: true },
  });

  return ok(updated);
}
