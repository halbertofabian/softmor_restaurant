import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { Navigate, useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { login } from '../../lib/api/auth'
import { ApiError } from '../../lib/api/client'
import { getDeviceName } from '../../lib/device'
import { useAuthStore } from '../../stores/authStore'

const loginSchema = z.object({
  email: z.email('Ingresa un correo válido'),
  password: z.string().min(1, 'Ingresa tu contraseña'),
})

type LoginFormValues = z.infer<typeof loginSchema>

export function LoginPage() {
  const navigate = useNavigate()
  const token = useAuthStore((state) => state.token)
  const setSession = useAuthStore((state) => state.setSession)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: '', password: '' },
  })

  if (token) {
    return <Navigate to="/" replace />
  }

  const onSubmit = handleSubmit(async (values) => {
    try {
      const response = await login({ ...values, device_name: getDeviceName() })

      setSession({
        token: response.token,
        user: response.user,
        role: response.role,
        tenantId: response.tenant_id,
        branches: response.branches,
      })

      navigate('/', { replace: true })
    } catch (error) {
      const message =
        error instanceof ApiError ? error.message : 'No se pudo iniciar sesión. Intenta de nuevo.'

      setError('root', { message })
    }
  })

  return (
    <div className="bg-grid relative flex min-h-screen items-center justify-center overflow-x-hidden bg-ink p-4">
      <div className="hero-mesh" />

      <div className="relative z-10 w-full max-w-md">
        <div className="mb-8 flex justify-center">
          <div className="flex items-center gap-3">
            <img
              src="/pwa/gestionalfood.png"
              alt="GestionalFood"
              className="h-12 w-12 rounded-xl shadow-lg"
            />
            <span className="text-2xl font-extrabold tracking-tight text-white">
              GestionalFood<span className="text-primary">.</span>
            </span>
          </div>
        </div>

        <div className="relative overflow-hidden rounded-3xl border border-white/5 bg-card p-8 shadow-2xl shadow-black/50">
          <div className="pointer-events-none absolute right-0 top-0 h-20 w-20 rounded-full bg-primary/10 blur-[40px]" />

          <div className="mb-8 text-center">
            <h1 className="mb-2 text-2xl font-bold text-white">Bienvenido de nuevo</h1>
            <p className="text-sm text-gray-400">Ingresa tus credenciales para acceder</p>
          </div>

          <form onSubmit={onSubmit} className="flex flex-col gap-5" noValidate>
            <div>
              <label htmlFor="email" className="mb-2 block text-sm font-medium text-gray-400">
                Correo Electrónico
              </label>
              <input
                id="email"
                type="email"
                autoComplete="username"
                inputMode="email"
                placeholder="tu@correo.com"
                className="form-input w-full rounded-xl px-4 py-3"
                {...register('email')}
              />
              {errors.email && <p className="mt-1 text-xs text-red-400">{errors.email.message}</p>}
            </div>

            <div>
              <label htmlFor="password" className="mb-2 block text-sm font-medium text-gray-400">
                Contraseña
              </label>
              <input
                id="password"
                type="password"
                autoComplete="current-password"
                placeholder="••••••••"
                className="form-input w-full rounded-xl px-4 py-3"
                {...register('password')}
              />
              {errors.password && (
                <p className="mt-1 text-xs text-red-400">{errors.password.message}</p>
              )}
            </div>

            {errors.root && (
              <div className="rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                {errors.root.message}
              </div>
            )}

            <button
              type="submit"
              disabled={isSubmitting}
              className="mt-2 w-full rounded-xl bg-primary py-3.5 font-bold text-black shadow-lg shadow-primary/20 transition hover:-translate-y-px hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
            >
              {isSubmitting ? 'Ingresando…' : 'Ingresar al Sistema'}
            </button>
          </form>

          <div className="mt-8 border-t border-white/5 pt-6 text-center">
            <p className="text-xs text-gray-500">
              Usa tus credenciales de GestionalFood · Este dispositivo: {getDeviceName()}
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}
