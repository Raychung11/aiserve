import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth, AuthError } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function GET(req: NextRequest) {
  try {
    const { searchParams } = new URL(req.url);
    const page = parseInt(searchParams.get('page') ?? '1');
    const pageSize = parseInt(searchParams.get('pageSize') ?? '24');
    const search = searchParams.get('search') ?? '';
    const categoryId = searchParams.get('categoryId') ?? '';
    const requiresPrescription = searchParams.get('requiresPrescription');
    const dispensaryId = searchParams.get('dispensaryId') ?? '';

    const where: Record<string, unknown> = { isActive: true, deletedAt: null };
    if (search) where.name = { contains: search, mode: 'insensitive' };
    if (categoryId) where.categoryId = categoryId;
    if (dispensaryId) where.dispensaryId = dispensaryId;
    if (requiresPrescription !== null && requiresPrescription !== '')
      where.requiresPrescription = requiresPrescription === 'true';

    const [products, total] = await Promise.all([
      prisma.product.findMany({
        where,
        include: {
          category: { select: { id: true, name: true, slug: true } },
          dispensary: { select: { id: true, name: true } },
        },
        orderBy: { name: 'asc' },
        skip: (page - 1) * pageSize,
        take: pageSize,
      }),
      prisma.product.count({ where }),
    ]);

    return ok({ data: products, total, page, pageSize, totalPages: Math.ceil(total / pageSize) });
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to fetch products', 500);
  }
}

export async function POST(req: NextRequest) {
  try {
    const user = await requireAuth(['SUPER_ADMIN', 'DISPENSARY_STAFF']);
    const body = await req.json() as {
      sku: string;
      name: string;
      description?: string;
      price: number;
      stockQuantity: number;
      requiresPrescription: boolean;
      categoryId?: string;
      dispensaryId: string;
    };

    if (!body.dispensaryId) return fail('dispensaryId is required', 400);

    const product = await prisma.product.create({
      data: {
        sku: body.sku,
        name: body.name,
        description: body.description,
        price: body.price,
        stockQuantity: body.stockQuantity ?? 0,
        requiresPrescription: body.requiresPrescription ?? false,
        categoryId: body.categoryId,
        dispensaryId: body.dispensaryId,
      },
    });

    return ok(product, 201);
  } catch (err) {
    if (err instanceof AuthError) return fail(err.message, err.statusCode);
    return fail('Failed to create product', 500);
  }
}
