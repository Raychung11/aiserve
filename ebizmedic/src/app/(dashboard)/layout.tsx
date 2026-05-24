import { redirect } from 'next/navigation';
import { getCurrentUser } from '@/lib/auth';
import { Sidebar } from '@/components/layout/sidebar';
import { Header } from '@/components/layout/header';
import type { UserRole } from '@/types';

export default async function DashboardLayout({ children }: { children: React.ReactNode }) {
  const user = await getCurrentUser();
  if (!user) redirect('/login');

  return (
    <div className="flex h-screen overflow-hidden bg-gray-50">
      <Sidebar role={user.role as UserRole} userName={user.name} userEmail={user.email} />
      <div className="flex flex-1 flex-col overflow-hidden">
        <Header title="eBizMedic" role={user.role as UserRole} />
        <main className="flex-1 overflow-y-auto p-6">{children}</main>
      </div>
    </div>
  );
}
