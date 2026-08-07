import React from 'react'
import { hydrateRoot } from 'react-dom/client'
import { StartClient } from '@tanstack/react-start'
import { QueryClientProvider } from '@tanstack/react-query'
import { getRouter } from '@/router'
import { queryClient } from '@/lib/query'
import { ToastProvider } from '@/components/ui'
import '@/styles.css'
const router=getRouter()
hydrateRoot(document,<React.StrictMode><QueryClientProvider client={queryClient}><ToastProvider><StartClient router={router}/></ToastProvider></QueryClientProvider></React.StrictMode>)
