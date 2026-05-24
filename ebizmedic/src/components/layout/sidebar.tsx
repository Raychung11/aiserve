'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  LayoutDashboard,
  Building2,
  Users,
  Stethoscope,
  Calendar,
  Wallet,
  ShoppingBag,
  Package,
  FileText,
  BarChart3,
  Settings,
  Shield,
  Pill,
  Ambulance,
  ClipboardList,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { UserRole } from '@/types';

interface NavItem {
  label: string;
  href: string;
  icon: React.ElementType;
}

const NAV_ITEMS: Record<UserRole, NavItem[]> = {
  SUPER_ADMIN: [
    { label: 'Dashboard', href: '/admin', icon: LayoutDashboard },
    { label: 'Organisations', href: '/admin/organisations', icon: Building2 },
    { label: 'Doctors', href: '/admin/doctors', icon: Stethoscope },
    { label: 'Users', href: '/admin/users', icon: Users },
    { label: 'Bookings', href: '/admin/bookings', icon: Calendar },
    { label: 'Marketplace', href: '/admin/marketplace', icon: ShoppingBag },
    { label: 'Dispensaries', href: '/admin/dispensaries', icon: Package },
    { label: 'Reports', href: '/admin/reports', icon: BarChart3 },
    { label: 'Audit Logs', href: '/admin/audit', icon: Shield },
    { label: 'Settings', href: '/admin/settings', icon: Settings },
  ],
  ORG_ADMIN: [
    { label: 'Dashboard', href: '/organisation', icon: LayoutDashboard },
    { label: 'Staff', href: '/organisation/staff', icon: Users },
    { label: 'Wallet & Billing', href: '/organisation/wallet', icon: Wallet },
    { label: 'Bookings', href: '/organisation/bookings', icon: Calendar },
    { label: 'Reports', href: '/organisation/reports', icon: BarChart3 },
    { label: 'Settings', href: '/organisation/settings', icon: Settings },
  ],
  PATIENT: [
    { label: 'Dashboard', href: '/patient', icon: LayoutDashboard },
    { label: 'Book Consultation', href: '/patient/book', icon: Stethoscope },
    { label: 'My Appointments', href: '/patient/appointments', icon: Calendar },
    { label: 'Marketplace', href: '/patient/marketplace', icon: ShoppingBag },
    { label: 'My Orders', href: '/patient/orders', icon: Package },
    { label: 'Prescriptions', href: '/patient/prescriptions', icon: Pill },
    { label: 'My Records', href: '/patient/records', icon: FileText },
    { label: 'Profile', href: '/patient/profile', icon: Users },
  ],
  DOCTOR: [
    { label: 'Dashboard', href: '/doctor', icon: LayoutDashboard },
    { label: 'My Schedule', href: '/doctor/schedule', icon: Calendar },
    { label: 'Appointments', href: '/doctor/appointments', icon: ClipboardList },
    { label: 'Prescriptions', href: '/doctor/prescriptions', icon: Pill },
    { label: 'Profile', href: '/doctor/profile', icon: Users },
  ],
  DISPENSARY_STAFF: [
    { label: 'Dashboard', href: '/dispensary', icon: LayoutDashboard },
    { label: 'Products', href: '/dispensary/products', icon: Package },
    { label: 'Orders', href: '/dispensary/orders', icon: ShoppingBag },
    { label: 'Prescriptions', href: '/dispensary/prescriptions', icon: Pill },
    { label: 'Profile', href: '/dispensary/profile', icon: Users },
  ],
  MOBILE_MEDIC: [
    { label: 'Dashboard', href: '/medic', icon: LayoutDashboard },
    { label: 'Assignments', href: '/medic/assignments', icon: Ambulance },
    { label: 'Reports', href: '/medic/reports', icon: FileText },
    { label: 'Profile', href: '/medic/profile', icon: Users },
  ],
};

interface SidebarProps {
  role: UserRole;
  userName: string;
  userEmail: string;
}

export function Sidebar({ role, userName, userEmail }: SidebarProps) {
  const pathname = usePathname();
  const items = NAV_ITEMS[role] ?? [];

  return (
    <aside className="flex h-full w-64 flex-col border-r border-gray-200 bg-white">
      {/* Logo */}
      <div className="flex h-16 items-center gap-2.5 border-b border-gray-200 px-6">
        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600">
          <Stethoscope className="h-4 w-4 text-white" />
        </div>
        <span className="text-lg font-bold text-gray-900">eBizMedic</span>
      </div>

      {/* Nav */}
      <nav className="flex-1 overflow-y-auto px-3 py-4">
        <ul className="space-y-1">
          {items.map((item) => {
            const active = pathname === item.href || (item.href !== '/admin' && item.href !== '/organisation' && item.href !== '/doctor' && item.href !== '/patient' && item.href !== '/dispensary' && item.href !== '/medic' && pathname.startsWith(item.href));
            return (
              <li key={item.href}>
                <Link
                  href={item.href}
                  className={cn(
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    active
                      ? 'bg-primary-50 text-primary-700'
                      : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                  )}
                >
                  <item.icon className={cn('h-4 w-4', active ? 'text-primary-600' : 'text-gray-400')} />
                  {item.label}
                </Link>
              </li>
            );
          })}
        </ul>
      </nav>

      {/* User footer */}
      <div className="border-t border-gray-200 p-4">
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">
            {userName.slice(0, 2).toUpperCase()}
          </div>
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-medium text-gray-900">{userName}</p>
            <p className="truncate text-xs text-gray-500">{userEmail}</p>
          </div>
        </div>
      </div>
    </aside>
  );
}
