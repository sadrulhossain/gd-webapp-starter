import { ApiError } from '@gd/api-client'
import { QueryClient } from '@tanstack/react-query'
import { useAuth } from './auth/store'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      // A 4xx will not fix itself on retry.
      retry: (count, error) => !(error instanceof ApiError && error.status < 500) && count < 2,
    },
  },
})

// Cached menus, counts and lists belong to one account: drop them whenever the session ends,
// so the next person to sign in on this tab never sees the previous user's navigation.
useAuth.subscribe((state, previous) => {
  if (state.status === 'anonymous' && previous.status !== 'anonymous') queryClient.clear()
})
