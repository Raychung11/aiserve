import { redirect } from 'next/navigation';
import { getCurrentUser } from '@/lib/auth';
import { getDashboardPath } from '@/types/roles';
import type { UserRole } from '@/types';

export default async function RootPage() {
  const user = await getCurrentUser();
  if (user) redirect(getDashboardPath(user.role as UserRole));
  redirect('/login');
}
