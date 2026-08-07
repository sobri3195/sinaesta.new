import type { Config } from 'tailwindcss'
export default { content: ['./index.html', './app/**/*.{ts,tsx}'], theme: { extend: { colors: { ink:'#102a2e', brand:'#0d7c74', lime:'#d8f56a', mist:'#f2f7f5' }, boxShadow:{soft:'0 18px 50px rgba(16,42,46,.10)'} } }, plugins: [] } satisfies Config
