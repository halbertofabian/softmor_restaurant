import { createBrowserRouter, Navigate, Outlet, ScrollRestoration } from 'react-router-dom'
import { BranchSelectPage } from '../features/auth/BranchSelectPage'
import { LoginPage } from '../features/auth/LoginPage'
import { DashboardPage } from '../features/dashboard/DashboardPage'
import { TablesPage } from '../features/tables/TablesPage'
import { AppLayout } from './AppLayout'
import { RequireAuth, RequireBranch } from './guards'
import { InstallGate } from './InstallGate'
import { lazyPage } from './lazy-page'
import { RouteError } from './RouteError'

export const router = createBrowserRouter(
  [
    {
      element: (
        <>
          <ScrollRestoration />
          <InstallGate>
            <Outlet />
          </InstallGate>
        </>
      ),
      errorElement: <RouteError />,
      children: [
        { path: '/login', element: <LoginPage /> },
        {
          element: <RequireAuth />,
          children: [
            { path: '/select-branch', element: <BranchSelectPage /> },
            {
              element: <RequireBranch />,
              children: [
                {
                  element: <AppLayout />,
                  children: [
                    { index: true, element: <DashboardPage /> },
                    { path: '/mesas', element: <TablesPage /> },
                    {
                      path: '/orders/:orderId',
                      element: lazyPage(() =>
                        import('../features/orders/OrderPage').then((module) => ({
                          default: module.OrderPage,
                        })),
                      ),
                    },
                    {
                      path: '/historial',
                      element: lazyPage(() =>
                        import('../features/history/HistoryPage').then((module) => ({
                          default: module.HistoryPage,
                        })),
                      ),
                    },
                    {
                      path: '/impresoras',
                      element: lazyPage(() =>
                        import('../features/printers/PrintersPage').then((module) => ({
                          default: module.PrintersPage,
                        })),
                      ),
                    },
                  ],
                },
              ],
            },
          ],
        },
        { path: '*', element: <Navigate to="/" replace /> },
      ],
    },
  ],
  { basename: import.meta.env.BASE_URL },
)
