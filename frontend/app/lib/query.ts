import { QueryClient } from '@tanstack/react-query'

export const queryKeys = {
  auth: ['auth'] as const, profile: ['profile'] as const, packages: ['packages'] as const,
  questions: ['questions'] as const, tryouts: ['tryouts'] as const, attempts: ['attempts'] as const,
  analytics: ['analytics'] as const, transactions: ['transactions'] as const,
  adminUsers: ['admin-users'] as const, adminQuestions: ['admin-questions'] as const,
  adminPackages: ['admin-packages'] as const, adminTransactions: ['admin-transactions'] as const,
}

export const queryClient = new QueryClient({ defaultOptions: { queries: { staleTime: 30_000, retry: 1, refetchOnWindowFocus: false } } })

export const invalidateAfter = {
  profile: () => Promise.all([queryClient.invalidateQueries({queryKey:queryKeys.profile}), queryClient.invalidateQueries({queryKey:queryKeys.auth})]),
  answer: (attemptId:string) => queryClient.invalidateQueries({queryKey:[...queryKeys.attempts, attemptId], refetchType:'none'}),
  transaction: () => Promise.all([queryClient.invalidateQueries({queryKey:queryKeys.transactions}), queryClient.invalidateQueries({queryKey:queryKeys.packages})]),
}
