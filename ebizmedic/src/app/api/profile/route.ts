import { NextRequest } from 'next/server';
import { prisma } from '@/lib/db';
import { requireAuth } from '@/lib/auth';
import { ok, fail } from '@/lib/utils';

export async function GET() {
  const user = await requireAuth();

  const fullUser = await prisma.user.findUnique({
    where: { id: user.id },
    select: {
      id: true, name: true, email: true, phone: true, avatarUrl: true, role: true, createdAt: true,
      doctorProfile: { select: { id: true, licenseNo: true, bio: true, yearsExperience: true, consultationFee: true, isVerified: true, isAvailableOnline: true, isAvailableOnsite: true, rating: true, totalReviews: true } },
      patientProfile: { select: { id: true, dateOfBirth: true, gender: true, bloodGroup: true, allergies: true, medicalNotes: true, emergencyContact: true } },
    },
  });
  if (!fullUser) return fail('User not found', 404);

  return ok(fullUser);
}

export async function PATCH(req: NextRequest) {
  const user = await requireAuth();
  const body = await req.json();

  const { name, phone } = body;

  const updatedUser = await prisma.user.update({
    where: { id: user.id },
    data: {
      ...(name && { name }),
      ...(phone !== undefined && { phone }),
    },
    select: { id: true, name: true, email: true, phone: true, role: true },
  });

  if (user.role === 'DOCTOR' && body.doctor) {
    const { licenseNo, bio, yearsExperience, consultationFee, isAvailableOnline, isAvailableOnsite } = body.doctor;
    await prisma.doctorProfile.upsert({
      where: { userId: user.id },
      create: { userId: user.id, licenseNo, bio, yearsExperience, consultationFee },
      update: {
        ...(licenseNo !== undefined && { licenseNo }),
        ...(bio !== undefined && { bio }),
        ...(yearsExperience !== undefined && { yearsExperience: Number(yearsExperience) }),
        ...(consultationFee !== undefined && { consultationFee }),
        ...(isAvailableOnline !== undefined && { isAvailableOnline }),
        ...(isAvailableOnsite !== undefined && { isAvailableOnsite }),
      },
    });
  }

  if (user.role === 'PATIENT' && body.patient) {
    const { dateOfBirth, gender, bloodGroup, allergies, medicalNotes, emergencyContact } = body.patient;
    await prisma.patientProfile.update({
      where: { userId: user.id },
      data: {
        ...(dateOfBirth !== undefined && { dateOfBirth: dateOfBirth ? new Date(dateOfBirth) : null }),
        ...(gender !== undefined && { gender }),
        ...(bloodGroup !== undefined && { bloodGroup }),
        ...(allergies !== undefined && { allergies }),
        ...(medicalNotes !== undefined && { medicalNotes }),
        ...(emergencyContact !== undefined && { emergencyContact }),
      },
    });
  }

  return ok(updatedUser);
}
