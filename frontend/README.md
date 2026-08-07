# SINAESTA frontend

Frontend React + TypeScript memakai ekosistem TanStack, Tailwind, Zod, dan Recharts.

```bash
cp .env.example .env
npm install
npm run dev
```

API selalu dibaca dari `VITE_API_BASE_URL`; cookie sesi dikirim dengan kebijakan
`credentials: include`. Halaman publik hanya memakai data publik dan aman untuk
dirender pada server. Route aplikasi dan admin tetap membutuhkan enforcement izin
oleh API—guard client bukan pengganti authorization server.
