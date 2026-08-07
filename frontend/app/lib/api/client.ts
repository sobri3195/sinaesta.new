const DEFAULT_BASE_URL = 'https://api.sinaesta.id/api/v1'

export type ApiEnvelope<T> = { data: T; message?: string; meta?: Record<string, unknown>; requestId: string }
export type ApiErrorBody = { message?: string; code?: string; errors?: Record<string, string[]> }

export class ApiError extends Error {
  constructor(public status:number, message:string, public code?:string, public fields?:Record<string,string[]>, public requestId?:string) { super(message); this.name='ApiError' }
  get kind() { return ({401:'unauthorized',403:'forbidden',404:'not-found',409:'conflict',422:'validation',429:'rate-limited',500:'server'} as Record<number,string>)[this.status] ?? 'request' }
}

type Options = Omit<RequestInit,'body'> & { body?:unknown|FormData; timeoutMs?:number; credentials?:RequestCredentials; retry?:number; checkout?:boolean }
type StatusHandler = (error:ApiError)=>void
const handlers = new Map<number,StatusHandler>()
export const onApiStatus = (status:401|403|404|409|422|429|500, handler:StatusHandler) => { handlers.set(status,handler); return () => handlers.delete(status) }

const requestId = () => globalThis.crypto?.randomUUID?.() ?? `req-${Date.now()}-${Math.random().toString(16).slice(2)}`
const sleep = (ms:number, signal:AbortSignal) => new Promise<void>((resolve,reject)=>{ const id=setTimeout(resolve,ms); signal.addEventListener('abort',()=>{clearTimeout(id);reject(signal.reason)},{once:true}) })

export async function apiRequest<T>(path:string, options:Options={}):Promise<ApiEnvelope<T>> {
  const method=(options.method ?? 'GET').toUpperCase(), id=requestId(), timeout=options.timeoutMs ?? 12_000
  const controller=new AbortController(), timer=setTimeout(()=>controller.abort(new DOMException('Request timeout','TimeoutError')),timeout)
  if (options.signal) options.signal.addEventListener('abort',()=>controller.abort(options.signal?.reason),{once:true})
  const isForm=options.body instanceof FormData
  const headers=new Headers(options.headers); headers.set('Accept','application/json'); headers.set('X-Request-ID',id)
  if (options.body && !isForm) headers.set('Content-Type','application/json')
  const attempts=method==='GET' && !options.checkout ? Math.min(options.retry ?? 2,2)+1 : 1
  try {
    for(let attempt=0;attempt<attempts;attempt++) {
      try {
        // Request bodies are never logged. Sensitive values must reach the API unchanged.
        const response=await fetch(`${import.meta.env.VITE_API_BASE_URL || DEFAULT_BASE_URL}${path}`,{...options,method,headers,credentials:options.credentials ?? 'include',signal:controller.signal,body:options.body ? (isForm?options.body:JSON.stringify(options.body)) : undefined})
        const payload=(await response.json().catch(()=>({}))) as ApiErrorBody & {data?:T;meta?:Record<string,unknown>}
        if(!response.ok){ const error=new ApiError(response.status,payload.message || 'Permintaan tidak dapat diproses.',payload.code,payload.errors,response.headers.get('X-Request-ID')||id); handlers.get(response.status)?.(error); if(attempt<attempts-1 && (response.status>=500||response.status===429)){await sleep(300*2**attempt,controller.signal);continue} throw error }
        return {data:payload.data as T,message:payload.message,meta:payload.meta,requestId:response.headers.get('X-Request-ID')||id}
      } catch(error) { if(attempt<attempts-1 && !(error instanceof ApiError) && !controller.signal.aborted){await sleep(300*2**attempt,controller.signal);continue} throw error }
    }
    throw new Error('Unexpected request state')
  } finally { clearTimeout(timer) }
}

export const api={ get:<T>(path:string,options?:Options)=>apiRequest<T>(path,{...options,method:'GET'}), post:<T>(path:string,body?:unknown|FormData,options?:Options)=>apiRequest<T>(path,{...options,method:'POST',body}), patch:<T>(path:string,body?:unknown|FormData,options?:Options)=>apiRequest<T>(path,{...options,method:'PATCH',body}), delete:<T>(path:string,options?:Options)=>apiRequest<T>(path,{...options,method:'DELETE'}) }
