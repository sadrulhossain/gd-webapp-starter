import { useQuery } from '@tanstack/react-query'
import { api } from '../api'
import { useAuth } from '../auth/store'
import type { MenuItem } from '../types'

/** The signed-in user's navigation tree (/me/menus: already filtered by permission and by the hidden flag). Keyed by user. */
export function useMenus() {
  const userId = useAuth((s) => s.user?.id)

  return useQuery({
    queryKey: ['menus', userId],
    queryFn: async () => ((await api.request('GET', '/v1/me/menus')) as { data: MenuItem[] }).data,
    enabled: userId !== undefined,
  })
}

/** Path of the first link in display order, depth first; null when the tree has none. */
export function firstPath(items: MenuItem[]): string | null {
  for (const item of items) {
    if (item.path) return item.path

    const nested = firstPath(item.children)
    if (nested) return nested
  }

  return null
}
