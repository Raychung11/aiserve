import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { format, formatDistanceToNow } from 'date-fns';
import { NextResponse } from 'next/server';
import type { ApiResponse } from '@/types';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function ok<T>(data: T, status = 200): NextResponse<ApiResponse<T>> {
  return NextResponse.json({ success: true, data }, { status });
}

export function fail(error: string, status = 400): NextResponse<ApiResponse> {
  return NextResponse.json({ success: false, error }, { status });
}

export function formatCurrency(amount: number | string, currency = 'MYR'): string {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount;
  return new Intl.NumberFormat('en-MY', { style: 'currency', currency }).format(num);
}

export function formatDate(date: Date | string): string {
  return format(new Date(date), 'dd MMM yyyy');
}

export function formatDateTime(date: Date | string): string {
  return format(new Date(date), 'dd MMM yyyy, hh:mm a');
}

export function timeAgo(date: Date | string): string {
  return formatDistanceToNow(new Date(date), { addSuffix: true });
}

export function generateBookingRef(): string {
  const prefix = 'BK';
  const timestamp = Date.now().toString(36).toUpperCase();
  const random = Math.random().toString(36).substring(2, 6).toUpperCase();
  return `${prefix}-${timestamp}-${random}`;
}

export function getInitials(name: string): string {
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
}

export function paginate<T>(data: T[], page: number, pageSize: number) {
  const total = data.length;
  const totalPages = Math.ceil(total / pageSize);
  const offset = (page - 1) * pageSize;
  return {
    data: data.slice(offset, offset + pageSize),
    total,
    page,
    pageSize,
    totalPages,
  };
}
