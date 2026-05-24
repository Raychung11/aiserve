'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Button, type ButtonProps } from './button';

interface ActionButtonProps extends Omit<ButtonProps, 'onClick' | 'loading'> {
  url: string;
  method?: 'PATCH' | 'POST' | 'DELETE';
  body?: Record<string, unknown>;
  confirm?: string;
  onSuccess?: () => void;
}

export function ActionButton({
  url,
  method = 'PATCH',
  body,
  confirm: confirmMsg,
  onSuccess,
  children,
  ...props
}: ActionButtonProps) {
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  async function handleClick() {
    if (confirmMsg && !window.confirm(confirmMsg)) return;
    setLoading(true);
    try {
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: body ? JSON.stringify(body) : undefined,
      });
      if (!res.ok) {
        const json = await res.json().catch(() => ({}));
        alert(json.error ?? 'Action failed');
        return;
      }
      onSuccess?.();
      router.refresh();
    } catch {
      alert('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <Button loading={loading} onClick={handleClick} {...props}>
      {children}
    </Button>
  );
}
