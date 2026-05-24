import type { UserRole } from './index';

export const ROLE_LABELS: Record<UserRole, string> = {
  SUPER_ADMIN: 'Platform Admin',
  ORG_ADMIN: 'Organisation Admin',
  PATIENT: 'Patient',
  DOCTOR: 'Doctor',
  DISPENSARY_STAFF: 'Dispensary Staff',
  MOBILE_MEDIC: 'Mobile Medic',
};

export const DASHBOARD_PATHS: Record<UserRole, string> = {
  SUPER_ADMIN: '/admin',
  ORG_ADMIN: '/organisation',
  PATIENT: '/patient',
  DOCTOR: '/doctor',
  DISPENSARY_STAFF: '/dispensary',
  MOBILE_MEDIC: '/medic',
};

// Route prefix → allowed roles
export const ROUTE_ROLE_MAP: Record<string, UserRole[]> = {
  '/admin': ['SUPER_ADMIN'],
  '/organisation': ['SUPER_ADMIN', 'ORG_ADMIN'],
  '/doctor': ['SUPER_ADMIN', 'DOCTOR'],
  '/patient': ['SUPER_ADMIN', 'PATIENT'],
  '/dispensary': ['SUPER_ADMIN', 'DISPENSARY_STAFF'],
  '/medic': ['SUPER_ADMIN', 'MOBILE_MEDIC'],
};

// Fine-grained permissions
export const PERMISSIONS = {
  'org:read': ['SUPER_ADMIN', 'ORG_ADMIN'] as UserRole[],
  'org:write': ['SUPER_ADMIN'] as UserRole[],
  'org:manage': ['SUPER_ADMIN', 'ORG_ADMIN'] as UserRole[],

  'staff:read': ['SUPER_ADMIN', 'ORG_ADMIN'] as UserRole[],
  'staff:write': ['SUPER_ADMIN', 'ORG_ADMIN'] as UserRole[],

  'wallet:read': ['SUPER_ADMIN', 'ORG_ADMIN'] as UserRole[],
  'wallet:topup': ['SUPER_ADMIN', 'ORG_ADMIN'] as UserRole[],
  'wallet:adjust': ['SUPER_ADMIN'] as UserRole[],

  'booking:create': ['PATIENT', 'SUPER_ADMIN'] as UserRole[],
  'booking:read_own': ['PATIENT', 'DOCTOR'] as UserRole[],
  'booking:read_all': ['SUPER_ADMIN', 'ORG_ADMIN'] as UserRole[],
  'booking:cancel': ['PATIENT', 'DOCTOR', 'SUPER_ADMIN'] as UserRole[],
  'booking:manage': ['SUPER_ADMIN'] as UserRole[],

  'doctor:read': ['SUPER_ADMIN', 'ORG_ADMIN', 'PATIENT', 'DOCTOR'] as UserRole[],
  'doctor:write': ['SUPER_ADMIN'] as UserRole[],
  'doctor:schedule': ['DOCTOR', 'SUPER_ADMIN'] as UserRole[],

  'prescription:write': ['DOCTOR'] as UserRole[],
  'prescription:read_own': ['PATIENT', 'DOCTOR'] as UserRole[],
  'prescription:read_all': ['SUPER_ADMIN', 'DISPENSARY_STAFF'] as UserRole[],

  'product:read': ['SUPER_ADMIN', 'ORG_ADMIN', 'PATIENT', 'DISPENSARY_STAFF'] as UserRole[],
  'product:write': ['SUPER_ADMIN', 'DISPENSARY_STAFF'] as UserRole[],

  'order:create': ['PATIENT'] as UserRole[],
  'order:fulfill': ['DISPENSARY_STAFF'] as UserRole[],
  'order:read_all': ['SUPER_ADMIN', 'ORG_ADMIN', 'DISPENSARY_STAFF'] as UserRole[],

  'recording:read': ['SUPER_ADMIN', 'DOCTOR', 'PATIENT'] as UserRole[],
  'recording:manage': ['SUPER_ADMIN'] as UserRole[],

  'report:read': ['SUPER_ADMIN', 'ORG_ADMIN'] as UserRole[],
  'audit:read': ['SUPER_ADMIN'] as UserRole[],

  'admin:access': ['SUPER_ADMIN'] as UserRole[],
} as const;

export type Permission = keyof typeof PERMISSIONS;

export function hasPermission(role: UserRole, permission: Permission): boolean {
  return (PERMISSIONS[permission] as UserRole[]).includes(role);
}

export function getDashboardPath(role: UserRole): string {
  return DASHBOARD_PATHS[role] ?? '/login';
}
