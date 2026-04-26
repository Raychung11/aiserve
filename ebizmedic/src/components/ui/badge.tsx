import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
  {
    variants: {
      variant: {
        default: 'bg-gray-100 text-gray-700',
        primary: 'bg-primary-100 text-primary-700',
        success: 'bg-emerald-100 text-emerald-700',
        warning: 'bg-amber-100 text-amber-700',
        danger: 'bg-red-100 text-red-700',
        purple: 'bg-purple-100 text-purple-700',
        outline: 'border border-gray-300 text-gray-700',
        secondary: 'bg-gray-100 text-gray-600',
        info: 'bg-blue-100 text-blue-700',
      },
    },
    defaultVariants: { variant: 'default' },
  }
);

export interface BadgeProps
  extends React.HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof badgeVariants> {}

export function Badge({ className, variant, ...props }: BadgeProps) {
  return <span className={cn(badgeVariants({ variant }), className)} {...props} />;
}

export function AppointmentStatusBadge({ status }: { status: string }) {
  const map: Record<string, VariantProps<typeof badgeVariants>['variant']> = {
    PENDING: 'warning',
    CONFIRMED: 'primary',
    RESERVED: 'purple',
    CANCELLED: 'danger',
    COMPLETED: 'success',
    NO_SHOW: 'danger',
    REJECTED: 'danger',
    RESCHEDULED: 'warning',
  };

  const labels: Record<string, string> = {
    PENDING: 'Pending',
    CONFIRMED: 'Confirmed',
    RESERVED: 'Reserved',
    CANCELLED: 'Cancelled',
    COMPLETED: 'Completed',
    NO_SHOW: 'No Show',
    REJECTED: 'Rejected',
    RESCHEDULED: 'Rescheduled',
  };

  return <Badge variant={map[status] ?? 'default'}>{labels[status] ?? status}</Badge>;
}

export function OrderStatusBadge({ status }: { status: string }) {
  const map: Record<string, VariantProps<typeof badgeVariants>['variant']> = {
    PENDING: 'warning',
    CONFIRMED: 'primary',
    PROCESSING: 'purple',
    PACKED: 'primary',
    OUT_FOR_DELIVERY: 'purple',
    COMPLETED: 'success',
    CANCELLED: 'danger',
    REFUNDED: 'default',
  };

  return (
    <Badge variant={map[status] ?? 'default'}>
      {status.replace(/_/g, ' ')}
    </Badge>
  );
}
