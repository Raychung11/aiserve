import { NextRequest } from 'next/server';
import bcrypt from 'bcryptjs';
import { prisma } from '@/lib/db';
import { registerSchema } from '@/lib/validations';
import { ok, fail } from '@/lib/utils';

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    const data = registerSchema.parse(body);

    const existing = await prisma.user.findUnique({ where: { email: data.email } });
    if (existing) return fail('An account with this email already exists', 409);

    const passwordHash = await bcrypt.hash(data.password, 12);

    const user = await prisma.$transaction(async (tx) => {
      const newUser = await tx.user.create({
        data: {
          email: data.email,
          name: data.name,
          passwordHash,
          phone: data.phone,
          role: data.role,
        },
        select: { id: true, email: true, name: true, role: true },
      });

      if (data.role === 'PATIENT') {
        await tx.patientProfile.create({ data: { userId: newUser.id } });
      } else if (data.role === 'DOCTOR') {
        await tx.doctorProfile.create({ data: { userId: newUser.id } });
      }

      return newUser;
    });

    return ok({ id: user.id, email: user.email, name: user.name, role: user.role }, 201);
  } catch (err) {
    if (err instanceof Error && err.name === 'ZodError') {
      return fail('Invalid registration data', 400);
    }
    console.error('[AUTH REGISTER]', err);
    return fail('Registration failed', 500);
  }
}
