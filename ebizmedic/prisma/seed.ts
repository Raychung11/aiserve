import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcryptjs';

const prisma = new PrismaClient();

async function main() {
  console.log('Seeding eBizMedic database…');

  // Super Admin
  const adminPassword = await bcrypt.hash('Admin@1234', 12);
  const admin = await prisma.user.upsert({
    where: { email: 'admin@ebizmedic.com' },
    update: {},
    create: {
      email: 'admin@ebizmedic.com',
      name: 'Platform Admin',
      passwordHash: adminPassword,
      role: 'SUPER_ADMIN',
      isEmailVerified: true,
    },
  });
  console.log('✓ Super admin:', admin.email);

  // Demo Organisation Admin
  const orgAdminPassword = await bcrypt.hash('OrgAdmin@1234', 12);
  const orgAdmin = await prisma.user.upsert({
    where: { email: 'orgadmin@demo.com' },
    update: {},
    create: {
      email: 'orgadmin@demo.com',
      name: 'Ahmad Razak',
      passwordHash: orgAdminPassword,
      role: 'ORG_ADMIN',
      phone: '+60123456789',
      isEmailVerified: true,
    },
  });

  const org = await prisma.organisation.upsert({
    where: { adminUserId: orgAdmin.id },
    update: {},
    create: {
      name: 'Demo Corporation Sdn Bhd',
      registrationNo: 'DC123456-A',
      industry: 'Technology',
      address: '123 Jalan Demo, Taman Tech',
      city: 'Kuala Lumpur',
      state: 'Wilayah Persekutuan',
      country: 'MY',
      phone: '+60312345678',
      email: 'health@democorp.com',
      adminUserId: orgAdmin.id,
    },
  });

  await prisma.organisationWallet.upsert({
    where: { organisationId: org.id },
    update: {},
    create: {
      organisationId: org.id,
      balance: 5000,
      lowBalanceThreshold: 500,
    },
  });
  console.log('✓ Organisation:', org.name);

  // Demo Doctor
  const doctorPassword = await bcrypt.hash('Doctor@1234', 12);
  const doctorUser = await prisma.user.upsert({
    where: { email: 'doctor@demo.com' },
    update: {},
    create: {
      email: 'doctor@demo.com',
      name: 'Dr. Sarah Lee',
      passwordHash: doctorPassword,
      role: 'DOCTOR',
      phone: '+60198765432',
      isEmailVerified: true,
    },
  });

  const doctor = await prisma.doctorProfile.upsert({
    where: { userId: doctorUser.id },
    update: {},
    create: {
      userId: doctorUser.id,
      licenseNo: 'MMC/2020/12345',
      bio: 'General practitioner with 8 years of experience in family medicine and preventive care.',
      yearsExperience: 8,
      consultationFee: 80,
      isVerified: true,
      isAvailableOnline: true,
      isAvailableOnsite: true,
      rating: 4.8,
    },
  });

  await prisma.doctorSpecialty.upsert({
    where: { id: `${doctor.id}-gp` },
    update: {},
    create: { id: `${doctor.id}-gp`, doctorId: doctor.id, specialty: 'General Practice', isPrimary: true },
  });

  // Weekday schedule Mon–Fri
  for (let day = 1; day <= 5; day++) {
    await prisma.doctorSchedule.create({
      data: {
        doctorId: doctor.id,
        dayOfWeek: day,
        startTime: '09:00',
        endTime: '17:00',
        slotDuration: 30,
        bufferTime: 5,
        isActive: true,
      },
    }).catch(() => {}); // ignore duplicates
  }
  console.log('✓ Doctor:', doctorUser.name);

  // Demo Patient
  const patientPassword = await bcrypt.hash('Patient@1234', 12);
  const patientUser = await prisma.user.upsert({
    where: { email: 'patient@demo.com' },
    update: {},
    create: {
      email: 'patient@demo.com',
      name: 'Nurul Izzah',
      passwordHash: patientPassword,
      role: 'PATIENT',
      phone: '+60187654321',
      isEmailVerified: true,
    },
  });

  await prisma.patientProfile.upsert({
    where: { userId: patientUser.id },
    update: {},
    create: {
      userId: patientUser.id,
      dateOfBirth: new Date('1990-05-15'),
      gender: 'Female',
      bloodGroup: 'O+',
    },
  });
  console.log('✓ Patient:', patientUser.name);

  // Demo Dispensary
  const dispensaryAdmin = await prisma.user.upsert({
    where: { email: 'dispensary@demo.com' },
    update: {},
    create: {
      email: 'dispensary@demo.com',
      name: 'Tan Wei Ming',
      passwordHash: await bcrypt.hash('Dispensary@1234', 12),
      role: 'DISPENSARY_STAFF',
      isEmailVerified: true,
    },
  });

  const dispensary = await prisma.dispensary.upsert({
    where: { id: 'seed-dispensary-001' },
    update: {},
    create: {
      id: 'seed-dispensary-001',
      name: 'eBizMedic Pharmacy KL',
      address: '456 Jalan Bukit Bintang',
      phone: '+60312345000',
      email: 'pharmacy@ebizmedic.com',
    },
  });

  await prisma.dispensaryStaff.upsert({
    where: { dispensaryId_userId: { dispensaryId: dispensary.id, userId: dispensaryAdmin.id } },
    update: {},
    create: { dispensaryId: dispensary.id, userId: dispensaryAdmin.id, role: 'manager' },
  });

  // Product categories
  const categories = ['Pain Relief', 'Antibiotics', 'Vitamins & Supplements', 'Skincare', 'Chronic Disease'];
  const createdCategories: Record<string, string> = {};
  for (const name of categories) {
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    const cat = await prisma.productCategory.upsert({
      where: { slug },
      update: {},
      create: { name, slug, isActive: true },
    });
    createdCategories[name] = cat.id;
  }

  // Sample products
  const sampleProducts = [
    { sku: 'MED-001', name: 'Paracetamol 500mg (100 tabs)', category: 'Pain Relief', price: 8.90, requiresPrescription: false },
    { sku: 'MED-002', name: 'Amoxicillin 500mg (21 caps)', category: 'Antibiotics', price: 25.00, requiresPrescription: true },
    { sku: 'MED-003', name: 'Vitamin C 1000mg (30 tabs)', category: 'Vitamins & Supplements', price: 18.50, requiresPrescription: false },
    { sku: 'MED-004', name: 'Metformin 500mg (100 tabs)', category: 'Chronic Disease', price: 12.00, requiresPrescription: true },
    { sku: 'MED-005', name: 'Ibuprofen 400mg (24 tabs)', category: 'Pain Relief', price: 9.80, requiresPrescription: false },
  ];

  for (const p of sampleProducts) {
    await prisma.product.upsert({
      where: { sku: p.sku },
      update: {},
      create: {
        sku: p.sku,
        name: p.name,
        price: p.price,
        stockQuantity: 100,
        requiresPrescription: p.requiresPrescription,
        dispensaryId: dispensary.id,
        categoryId: createdCategories[p.category],
        isActive: true,
      },
    });
  }
  console.log('✓ Dispensary & 5 products seeded');

  // Zoom host (placeholder)
  await prisma.zoomHost.upsert({
    where: { email: 'zoom-host@ebizmedic.com' },
    update: {},
    create: {
      email: 'zoom-host@ebizmedic.com',
      zoomUserId: 'your-zoom-user-id',
      accountId: process.env.ZOOM_ACCOUNT_ID ?? 'placeholder',
      clientId: process.env.ZOOM_CLIENT_ID ?? 'placeholder',
      clientSecret: process.env.ZOOM_CLIENT_SECRET ?? 'placeholder',
      isActive: true,
      maxLoad: 1,
    },
  });
  console.log('✓ Zoom host seeded');

  console.log('\n✅ Seed complete.\n');
  console.log('Demo accounts:');
  console.log('  Admin:       admin@ebizmedic.com       / Admin@1234');
  console.log('  Org Admin:   orgadmin@demo.com         / OrgAdmin@1234');
  console.log('  Doctor:      doctor@demo.com           / Doctor@1234');
  console.log('  Patient:     patient@demo.com          / Patient@1234');
  console.log('  Dispensary:  dispensary@demo.com       / Dispensary@1234');
}

main()
  .catch(console.error)
  .finally(() => prisma.$disconnect());
