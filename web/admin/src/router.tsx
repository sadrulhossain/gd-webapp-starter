import type { ComponentType } from 'react'
import { createBrowserRouter } from 'react-router'
import { RequireAuth, RequirePermission } from './auth/guards'
import { CrudPage, type CrudConfig } from './components/CrudPage'
import { AppShell } from './layout/AppShell'
import { modules } from './modules'
import { Callback, SignedOut } from './pages/auth'
import { Home } from './pages/Home'
import { NotFound } from './pages/errors'
import { ProfilePage } from './pages/Profile'
import { SettingsPage } from './pages/Settings'
import { settings } from './settingsConfig'
import { Showcase } from './showcase'

/** Modules that need more than the plain page; everything else renders CrudPage directly. */
const pages: Record<string, ComponentType<{ config: CrudConfig }>> = {}

export const router = createBrowserRouter([
  // Public: the OAuth redirect target, the post-logout page and the theme showcase.
  { path: '/auth/callback', element: <Callback /> },
  { path: '/signed-out', element: <SignedOut /> },
  { path: '/showcase', element: <Showcase /> },

  // Everything else needs a session; each page also needs its permission (403 otherwise).
  {
    element: (
      <RequireAuth>
        <AppShell />
      </RequireAuth>
    ),
    children: [
      {
        index: true,
        element: <Home />,
      },
      ...modules.map((config) => {
        const Page = pages[config.key] ?? CrudPage

        return {
          path: config.path.slice(1),
          element: (
            <RequirePermission permission={`${config.key}.view`}>
              <Page config={config} />
            </RequirePermission>
          ),
        }
      }),
      {
        path: settings.path.slice(1),
        element: (
          <RequirePermission permission={`${settings.key}.view`}>
            <SettingsPage config={settings} />
          </RequirePermission>
        ),
      },
      // Own profile: any signed-in user, no permission.
      { path: 'profile', element: <ProfilePage /> },
      { path: '*', element: <NotFound /> },
    ],
  },
])
