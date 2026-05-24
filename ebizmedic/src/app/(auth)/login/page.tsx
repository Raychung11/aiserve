'use client';

import { useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { loginSchema, type LoginInput } from '@/lib/validations';

export default function LoginPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const callbackUrl = searchParams.get('callbackUrl') ?? '';
  const [serverError, setServerError] = useState('');

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginInput>({ resolver: zodResolver(loginSchema) });

  async function onSubmit(values: LoginInput) {
    setServerError('');
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(values),
      });

      const data = (await res.json()) as { success: boolean; error?: string; data?: { redirectTo: string } };

      if (!res.ok || !data.success) {
        setServerError(data.error ?? 'Login failed. Please try again.');
        return;
      }

      router.push(callbackUrl || data.data?.redirectTo || '/');
      router.refresh();
    } catch {
      setServerError('Network error. Please try again.');
    }
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900">Sign in</h1>
      <p className="mt-1 text-sm text-gray-500">Enter your credentials to access your account</p>

      <form onSubmit={handleSubmit(onSubmit)} className="mt-8 space-y-4">
        <Input
          label="Email address"
          type="email"
          autoComplete="email"
          required
          error={errors.email?.message}
          {...register('email')}
        />

        <div>
          <Input
            label="Password"
            type="password"
            autoComplete="current-password"
            required
            error={errors.password?.message}
            {...register('password')}
          />
          <div className="mt-1 text-right">
            <Link href="/reset-password" className="text-xs text-primary-600 hover:underline">
              Forgot password?
            </Link>
          </div>
        </div>

        {serverError && (
          <p className="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700">{serverError}</p>
        )}

        <Button type="submit" className="w-full" loading={isSubmitting}>
          Sign in
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-gray-500">
        Don&apos;t have an account?{' '}
        <Link href="/register" className="font-medium text-primary-600 hover:underline">
          Register
        </Link>
      </p>
    </div>
  );
}
