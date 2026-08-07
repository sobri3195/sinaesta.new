import { createRootRoute, createRoute, createRouter, Outlet, redirect } from '@tanstack/react-router'
import type { ReactElement } from 'react'
import { AppLayout } from '@/components/layout'
import { Landing, Pricing, Faq, AuthPage } from '@/pages/public'
import { Dashboard } from '@/pages/dashboard'
import { Attempt } from '@/pages/attempt'
import { AdminDashboard, AdminResource } from '@/pages/admin'

const root=createRootRoute({component:()=> <Outlet/>,notFoundComponent:()=> <main className="container-app py-24 text-center"><p className="eyebrow">404</p><h1 className="mt-3 text-4xl font-black">Halaman tidak ditemukan</h1><a href="/" className="btn-primary mt-7">Kembali ke beranda</a></main>})
const route=(path:string,component:()=>ReactElement)=>createRoute({getParentRoute:()=>root,path,component})
const publicRoutes=[route('/',Landing),route('/pricing',Pricing),route('/faq',Faq),route('/login',()=> <AuthPage mode="login"/>),route('/register',()=> <AuthPage mode="register"/>),route('/forgot-password',()=> <AuthPage mode="forgot-password"/>),route('/reset-password',()=> <AuthPage mode="reset-password"/>),route('/verify-email',()=> <AuthPage mode="verify-email"/>)]
const appRoot=route('/app',()=> <AppLayout/>), dashboard=createRoute({getParentRoute:()=>appRoot,path:'/dashboard',component:Dashboard})
const ComingSoon=({title}:{title:string})=><><p className="eyebrow">Sistem ujian</p><h1 className="mt-2 text-3xl font-black">{title}</h1><div className="card mt-7"><p className="text-sm text-slate-500">Data pada halaman ini diambil dari layanan ujian SINAESTA.</p></div></>
const titles:Record<string,string>={exams:'Ujian & kuis',analytics:'Analisis hasil',leaderboard:'Ranking',history:'Riwayat ujian',packages:'Paket & token',transactions:'Pembayaran',settings:'Pengaturan'}
const appChildren=[dashboard,...Object.entries(titles).map(([path,title])=>createRoute({getParentRoute:()=>appRoot,path:`/${path}`,component:()=> <ComingSoon title={title}/> })),createRoute({getParentRoute:()=>appRoot,path:'/attempt/$attemptId',component:Attempt}),...['result/$attemptId','review/$attemptId','checkout/$packageId','invoices/$invoiceId'].map(path=>createRoute({getParentRoute:()=>appRoot,path:`/${path}`,component:()=> <ComingSoon title={path.split('/')[0].replace(/^./,c=>c.toUpperCase())}/> }))]
const adminRoot=route('/admin',()=> <AppLayout admin/>),adminDashboard=createRoute({getParentRoute:()=>adminRoot,path:'/dashboard',component:AdminDashboard})
const adminNames=['exams','questions','question-pools','attempts','results','packages','transactions','audit']
const adminChildren=[adminDashboard,...adminNames.map(name=>createRoute({getParentRoute:()=>adminRoot,path:`/${name}`,component:()=> <AdminResource title={name.split('-').map(x=>x[0].toUpperCase()+x.slice(1)).join(' ')}/> })),createRoute({getParentRoute:()=>adminRoot,path:'/questions/new',component:()=> <ComingSoon title="Buat soal"/>}),createRoute({getParentRoute:()=>adminRoot,path:'/questions/$questionId',component:()=> <ComingSoon title="Edit soal"/>})]
const appIndex=createRoute({getParentRoute:()=>appRoot,path:'/',beforeLoad:()=>{throw redirect({to:'/app/dashboard'})}}),adminIndex=createRoute({getParentRoute:()=>adminRoot,path:'/',beforeLoad:()=>{throw redirect({to:'/admin/dashboard'})}})
const routeTree=root.addChildren([...publicRoutes,appRoot.addChildren([appIndex,...appChildren]),adminRoot.addChildren([adminIndex,...adminChildren])])
// TanStack Start calls this factory for each SSR request, preventing router state
// from leaking between visitors while preserving the same typed route tree.
export function getRouter(){return createRouter({routeTree,defaultPreload:'intent',defaultPreloadStaleTime:30_000,scrollRestoration:true})}
declare module '@tanstack/react-router' { interface Register { router:ReturnType<typeof getRouter> } }
