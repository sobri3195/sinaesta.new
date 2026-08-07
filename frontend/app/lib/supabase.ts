type AuthResponse={access_token?:string;user?:{id:string;email?:string};error?:string;msg?:string}
const env=(import.meta as ImportMeta & {env:Record<string,string|undefined>}).env
const url=env.VITE_SUPABASE_URL?.replace(/\/$/,'')
const key=env.VITE_SUPABASE_ANON_KEY

async function auth(path:string,body:Record<string,unknown>,token?:string){
  if(!url||!key) throw new Error('Konfigurasi Supabase belum tersedia.')
  const response=await fetch(`${url}/auth/v1/${path}`,{method:'POST',headers:{apikey:key,Authorization:`Bearer ${token??key}`,'Content-Type':'application/json'},body:JSON.stringify(body)})
  const data=await response.json() as AuthResponse
  if(!response.ok) throw new Error(data.msg??data.error??'Permintaan autentikasi gagal.')
  return data
}

export const supabaseAuth={
  signUp:(email:string,password:string,metadata:Record<string,unknown>)=>auth('signup',{email,password,data:metadata}),
  signIn:(email:string,password:string)=>auth('token?grant_type=password',{email,password}),
  resetPassword:(email:string)=>auth('recover',{email}),
  verifyEmail:(token:string)=>auth('verify',{type:'signup',token}),
  logoutAll:(accessToken:string)=>auth('logout?scope=global',{},accessToken),
}
