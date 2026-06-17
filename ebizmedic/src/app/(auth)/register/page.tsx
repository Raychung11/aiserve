'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/ui/button';
import { Input, Select } from '@/components/ui/input';
import { registerSchema, type RegisterInput } from '@/lib/validations';

const ROLE_OPTIONS = [
  { value: 'PATIENT', label: 'Patient / Employee' },
  { value: 'DOCTOR', label: 'Doctor' },
  { value: 'ORG_ADMIN', label: 'Organisation Admin' },
  { value: 'DISPENSARY_STAFF', label: 'Dispensary Staff' },
  { value: 'MOBILE_MEDIC', label: 'Mobile Medic Provider' },
];

export default function RegisterPage() {
  const router = useRouter();
  const [serverError, setServerError] = useState('');
  const [success, setSuccess] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<RegisterInput>({ resolver: zodResolver(registerSchema) });

  async function onSubmit(values: RegisterInput) {
    setServerError('');
    try {
      const res = await fetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(values),
      });

      const data = (await res.json()) as { success: boolean; error?: string };

      if (!res.ok || !data.success) {
        setServerError(data.error ?? 'Registration failed.');
        return;
      }

      setSuccess(true);
      setTimeout(() => router.push('/login'), 2000);
    } catch {
      setServerError('Network error. Please try again.');
    }
  }

  if (success) {
    return (
      <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-8 text-center">
        <p className="text-xl font-semibold text-emerald-700">Account created!</p>
        <p className="mt-1 text-sm text-emerald-600">Redirecting to login…</p>
      </div>
    );
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900">Create account</h1>
      <p className="mt-1 text-sm text-gray-500">Join the eBizMedic healthcare platform</p>

      <form onSubmit={handleSubmit(onSubmit)} className="mt-8 space-y-4">
        <Input
          label="Full name"
          required
          error={errors.name?.message}
          {...register('name')}
        />
        <Input
          label="Email address"
          type="email"
          required
          error={errors.email?.message}
          {...register('email')}
        />
        <Input
          label="Phone number"
          type="tel"
          error={errors.phone?.message}
          {...register('phone')}
        />
        <Input
          label="Password"
          type="password"
          required
          hint="Minimum 8 characters"
          error={errors.password?.message}
          {...register('password')}
        />
        <Select
          label="I am a"
          options={ROLE_OPTIONS}
          error={errors.role?.message}
          {...register('role')}
        />

        {serverError && (
          <p className="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700">{serverError}</p>
        )}

        <Button type="submit" className="w-full" loading={isSubmitting}>
          Create account
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-gray-500">
        Already have an account?{' '}
        <Link href="/login" className="font-medium text-primary-600 hover:underline">
          Sign in
        </Link>
      </p>
    </div>
  );
}
