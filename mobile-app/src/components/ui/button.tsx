import * as React from 'react';
import { cn } from '@/lib/utils';

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'default' | 'ghost';
  size?: 'default' | 'icon';
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant = 'default', size = 'default', ...props }, ref) => (
    <button
      ref={ref}
      className={cn(
        'inline-flex items-center justify-center transition-colors disabled:opacity-50',
        variant === 'ghost' && 'bg-transparent hover:bg-black/5',
        size === 'icon' && 'h-8 w-8',
        className
      )}
      {...props}
    />
  )
);
Button.displayName = 'Button';
