import { afterEach, describe, expect, it, vi } from 'vitest'
import { ApiError, apiRequest } from './client'

afterEach(()=>vi.restoreAllMocks())
describe('apiRequest',()=>{
  it('sends JSON, credentials, and a request ID without changing secrets',async()=>{
    const fetchMock=vi.spyOn(globalThis,'fetch').mockResolvedValue(new Response(JSON.stringify({data:{ok:true}}),{status:200,headers:{'Content-Type':'application/json'}}))
    await apiRequest<{ok:boolean}>('/login',{method:'POST',body:{password:'rahasia'}})
    const init=fetchMock.mock.calls[0][1]!
    expect(init.credentials).toBe('include')
    expect((init.headers as Headers).get('X-Request-ID')).toBeTruthy()
    expect(init.body).toBe(JSON.stringify({password:'rahasia'}))
  })
  it('maps validation responses to a typed error',async()=>{
    vi.spyOn(globalThis,'fetch').mockResolvedValue(new Response(JSON.stringify({message:'Periksa input',code:'VALIDATION',errors:{email:['Tidak valid']}}),{status:422,headers:{'Content-Type':'application/json'}}))
    await expect(apiRequest('/profile')).rejects.toMatchObject<ApiError>({status:422,code:'VALIDATION',kind:'validation'})
  })
  it('never retries checkout',async()=>{
    const fetchMock=vi.spyOn(globalThis,'fetch').mockResolvedValue(new Response('{}',{status:500}))
    await expect(apiRequest('/checkout',{checkout:true})).rejects.toBeInstanceOf(ApiError)
    expect(fetchMock).toHaveBeenCalledTimes(1)
  })
})
