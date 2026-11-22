import { Navigate } from 'react-router'
import { useAuth } from '../auth/store'
import { firstPath, useMenus } from '../lib/menus'
import { Dashboard } from './Dashboard'

/**
 * The landing page. A user with dashboard.view gets the dashboard; anyone else goes to the first page
 * their sidebar offers, so signing in never ends on a 403. With nothing at all, an explanation.
 */
export function Home() {
  const canDashboard = useAuth((s) => s.user?.permissions.includes('dashboard.view') ?? false)
  const menus = useMenus()

  if (canDashboard) return <Dashboard />
  if (menus.isPending) return <p role="status" className="py-10 text-center text-ink-muted">Loading...</p>

  const target = firstPath(menus.data ?? [])
  if (target && target !== '/') return <Navigate to={target} replace />

  return (
    <section className="space-y-2 py-10 text-center">
      <h1 className="text-2xl font-semibold">No pages yet</h1>
      <p className="text-ink-muted">Your account is active but has no access to any section. Ask an administrator to give your role some permissions.</p>
    </section>
  )
}
