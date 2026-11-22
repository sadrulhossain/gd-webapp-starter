import { describe, expect, it } from 'vitest'
import { useAuth } from './auth/store'
import { queryClient } from './queryClient'

describe('queryClient', () => {
  it("drops every cached query when the session ends, so the next account never sees the last one's menu", () => {
    useAuth.getState().setUser({ id: 1, name: 'A', email: 'a@example.test', is_superadmin: false, roles: [], permissions: [] })
    queryClient.setQueryData(['menus', 1], [{ id: 1 }])

    useAuth.getState().clear()

    expect(queryClient.getQueryData(['menus', 1])).toBeUndefined()
  })
})
