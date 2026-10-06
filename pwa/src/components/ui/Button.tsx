import type { ButtonHTMLAttributes, ReactNode } from 'react'

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger'
type Size = 'sm' | 'md' | 'lg'

const variants: Record<Variant, string> = {
  primary: 'bg-primary font-bold text-black shadow-lg shadow-primary/20 hover:bg-primary-dark',
  secondary: 'border border-white/10 text-gray-200 hover:bg-white/5',
  ghost: 'text-gray-400 hover:bg-white/5 hover:text-white',
  danger: 'border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/20',
}

const sizes: Record<Size, string> = {
  sm: 'px-3 py-1.5 text-xs',
  md: 'px-4 py-2.5 text-sm',
  lg: 'px-4 py-3.5 text-sm',
}

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: Variant
  size?: Size
  children: ReactNode
}

export function Button({
  variant = 'primary',
  size = 'md',
  className = '',
  ...props
}: ButtonProps) {
  return (
    <button
      className={`inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl font-semibold transition [&_svg]:shrink-0 disabled:cursor-not-allowed disabled:opacity-50 ${variants[variant]} ${sizes[size]} ${className}`}
      {...props}
    />
  )
}
