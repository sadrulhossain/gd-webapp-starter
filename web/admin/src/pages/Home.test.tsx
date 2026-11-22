import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { cleanup, render, screen, waitFor } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '../api'
import { useAuth } from '../auth/store'
import { firstPath } from '../lib/menus'
import type { MenuItem } from '../types'
import { Home } from './Home'

vi.mock('../api', () => ({ api: { request: vi.fn() } }))
vi.mock('./Dashboard', () => ({ Dashboard: () => <p>dashboard page</p> }))

const item = (id: number, path: string | null, children: MenuItem[] = []): MenuItem => ({ id, title: `t${id}`, icon: null, path, permission_name: null, children })
const me = (permissions: string[]) => ({ id: 7, name: 'A', email: 'a@example.test', is_superadmin: false, roles: [], permissions }) as never

function renderHome() {
  return render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <MemoryRouter initialEntries={['/']}>
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/roles" element={<p>roles page</p>} />
        </Routes>
      </MemoryRouter>
    </QueryClientProvider>,
  )
}

beforeEach(() => vi.mocked(api.request).mockReset())
afterEach(cleanup)

describe('firstPath', () => {
  it('finds the first link depth first and skips empty groups', () => {
    expect(firstPath([item(1, null, [item(2, null), item(3, '/roles')]), item(4, '/users')])).toBe('/roles')
    expect(firstPath([item(1, null)])).toBeNull()
    expect(firstPath([])).toBeNull()
  })
})

describe('Home', () => {
  it('shows the dashboard to a user who may view it', () => {
    useAuth.getState().setUser(me(['dashboard.view']))
    renderHome()

    expect(screen.getByText('dashboard page')).toBeTruthy()
  })

  it('sends a user without the dashboard to the first page their menu offers', async () => {
    useAuth.getState().setUser(me(['roles.view']))
    vi.mocked(api.request).mockResolvedValue({ data: [item(1, null, [item(2, '/roles')])] })
    renderHome()

    await waitFor(() => expect(screen.getByText('roles page')).toBeTruthy())
  })

  it('explains when there is nothing to open', async () => {
    useAuth.getState().setUser(me([]))
    vi.mocked(api.request).mockResolvedValue({ data: [] })
    renderHome()

    await waitFor(() => expect(screen.getByText('No pages yet')).toBeTruthy())
  })
})
