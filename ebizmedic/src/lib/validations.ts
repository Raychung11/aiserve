import { z } from 'zod';

export const loginSchema = z.object({
  email: z.string().email('Invalid email address'),
  password: z.string().min(8, 'Password must be at least 8 characters'),
});

export const registerSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  email: z.string().email('Invalid email address'),
  password: z.string().min(8, 'Password must be at least 8 characters'),
  phone: z.string().optional(),
  role: z.enum(['PATIENT', 'DOCTOR', 'ORG_ADMIN', 'DISPENSARY_STAFF', 'MOBILE_MEDIC']),
});

export const createBookingSchema = z.object({
  doctorId: z.string().cuid(),
  type: z.enum(['VISUAL_CONSULTATION', 'ONSITE_APPOINTMENT', 'MOBILE_MEDIC']),
  scheduledAt: z.string().datetime(),
  durationMinutes: z.number().int().min(15).max(120).default(30),
  notes: z.string().max(1000).optional(),
});

export const walletTopUpSchema = z.object({
  amount: z.number().positive('Amount must be positive'),
  description: z.string().max(500).optional(),
});

export const createOrganisationSchema = z.object({
  name: z.string().min(2),
  registrationNo: z.string().optional(),
  industry: z.string().optional(),
  address: z.string().optional(),
  city: z.string().optional(),
  state: z.string().optional(),
  phone: z.string().optional(),
  email: z.string().email().optional(),
});

export const createProductSchema = z.object({
  sku: z.string().min(1),
  name: z.string().min(1),
  description: z.string().optional(),
  price: z.number().positive(),
  stockQuantity: z.number().int().min(0).default(0),
  requiresPrescription: z.boolean().default(false),
  categoryId: z.string().cuid().optional(),
});

export const updateScheduleSchema = z.object({
  schedules: z.array(
    z.object({
      dayOfWeek: z.number().int().min(0).max(6),
      startTime: z.string().regex(/^\d{2}:\d{2}$/),
      endTime: z.string().regex(/^\d{2}:\d{2}$/),
      slotDuration: z.number().int().min(15).max(120).default(30),
      bufferTime: z.number().int().min(0).max(30).default(5),
      isActive: z.boolean().default(true),
    })
  ),
});

export type LoginInput = z.infer<typeof loginSchema>;
export type RegisterInput = z.infer<typeof registerSchema>;
export type CreateBookingInput = z.infer<typeof createBookingSchema>;
export type WalletTopUpInput = z.infer<typeof walletTopUpSchema>;
export type CreateOrganisationInput = z.infer<typeof createOrganisationSchema>;
