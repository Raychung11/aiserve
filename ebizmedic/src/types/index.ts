export type UserRole =
  | 'SUPER_ADMIN'
  | 'ORG_ADMIN'
  | 'PATIENT'
  | 'DOCTOR'
  | 'DISPENSARY_STAFF'
  | 'MOBILE_MEDIC';

export type AppointmentType =
  | 'VISUAL_CONSULTATION'
  | 'ONSITE_APPOINTMENT'
  | 'MOBILE_MEDIC';

export type AppointmentStatus =
  | 'PENDING'
  | 'CONFIRMED'
  | 'RESERVED'
  | 'CANCELLED'
  | 'COMPLETED'
  | 'NO_SHOW'
  | 'REJECTED'
  | 'RESCHEDULED';

export type TransactionType =
  | 'TOPUP'
  | 'DEDUCTION'
  | 'REFUND'
  | 'ADJUSTMENT'
  | 'REVERSAL';

export type ServiceCategory =
  | 'VISUAL_CONSULTATION'
  | 'ONSITE_APPOINTMENT'
  | 'MARKETPLACE'
  | 'MOBILE_MEDIC';

export type OrderStatus =
  | 'PENDING'
  | 'CONFIRMED'
  | 'PROCESSING'
  | 'PACKED'
  | 'OUT_FOR_DELIVERY'
  | 'COMPLETED'
  | 'CANCELLED'
  | 'REFUNDED';

export type RecordingStatus =
  | 'PENDING'
  | 'PROCESSING'
  | 'AVAILABLE'
  | 'BACKED_UP'
  | 'FAILED'
  | 'DELETED';

export interface TokenPayload {
  id: string;
  email: string;
  name: string;
  role: UserRole;
  iat?: number;
  exp?: number;
}

export interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  pageSize: number;
  totalPages: number;
}

export interface CreateBookingDto {
  doctorId: string;
  type: AppointmentType;
  scheduledAt: string;
  durationMinutes?: number;
  notes?: string;
}

export interface WalletTopUpDto {
  amount: number;
  description?: string;
}

export interface WalletDeductDto {
  amount: number;
  serviceCategory: ServiceCategory;
  referenceType: string;
  referenceId: string;
  staffUserId?: string;
  description?: string;
}
