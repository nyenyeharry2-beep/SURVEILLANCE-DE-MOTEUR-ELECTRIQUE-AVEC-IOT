import * as React from 'react';
import { cn } from '@/lib/utils';

export const Badge = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement> & { variant?: 'outline' }
>(({ className, variant = 'outline', ...props }, ref) => (
  <div
    ref={ref}
    className={cn(
      'inline-flex items-center rounded-none border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
      variant === 'outline' && 'bg-transparent',
      className
    )}
    {...props}
  />
));
Badge.displayName = 'Badge';
