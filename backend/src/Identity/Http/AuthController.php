<?php
declare(strict_types=1);
namespace Sinaesta\Identity\Http;
use Sinaesta\Identity\Application\AuthService; use Sinaesta\Identity\Infrastructure\AuthRepository; use Sinaesta\Shared\Http\HttpException; use Sinaesta\Shared\Http\Request; use Sinaesta\Shared\Http\Response;
final readonly class AuthController
{
    public function __construct(private AuthService $service,private AuthRepository $repository) {}
    public function register(Request $r): Response { return Response::success('Registrasi berhasil. Periksa email untuk verifikasi.',$this->service->register($r->json()),201); }
    public function login(Request $r): Response { $data=$this->service->login($r->json(),$r->header('user-agent')??'',$r->ip); $headers=[]; if(filter_var(getenv('AUTH_USE_COOKIE')?:'false',FILTER_VALIDATE_BOOL)){ $secure=filter_var(getenv('AUTH_COOKIE_SECURE')?:'true',FILTER_VALIDATE_BOOL);$cookie=(getenv('AUTH_COOKIE_NAME')?:'sinaesta_session').'='.rawurlencode($data['token']).'; Path=/; HttpOnly; SameSite=Lax'.($secure?'; Secure':'').((getenv('AUTH_COOKIE_DOMAIN')?:'')!==''?'; Domain='.getenv('AUTH_COOKIE_DOMAIN'):'');$csrf=bin2hex(random_bytes(24));$headers=['Set-Cookie'=>$cookie,'X-CSRF-Token'=>$csrf]; } return new Response(200,['success'=>true,'message'=>'Login berhasil.','data'=>$data,'meta'=>(object)[],'errors'=>null],$headers); }
    public function logout(Request $r): Response { $u=$r->attribute('user');$this->repository->revokeSession((int)$u['internal_id'],(string)$u['session_id']);return Response::success('Logout berhasil.',null,204); }
    public function logoutAll(Request $r): Response { $this->repository->revokeAllSessions((int)$r->attribute('user')['internal_id']);return Response::success('Semua sesi telah dicabut.'); }
    public function me(Request $r): Response { return Response::success('Pengguna berhasil diambil.',$this->service->publicUser($r->attribute('user'))); }
    public function forgot(Request $r): Response { $i=$r->json();$this->service->forgot((string)($i['email']??''));return Response::success('Jika email terdaftar, instruksi reset password akan dikirim.'); }
    public function reset(Request $r): Response { $i=$r->json();$this->service->reset((string)($i['token']??''),(string)($i['password']??''),(string)($i['password_confirmation']??''));return Response::success('Password berhasil direset.'); }
    public function change(Request $r): Response { $i=$r->json();$this->service->change($r->attribute('user'),(string)($i['current_password']??''),(string)($i['password']??''),(string)($i['password_confirmation']??''));return Response::success('Password berhasil diubah. Silakan login kembali.'); }
    public function resend(Request $r): Response { if($r->attribute('user')['email_verified_at']!==null)throw new HttpException(409,'Email sudah diverifikasi.');$token=$this->service->verificationToken((int)$r->attribute('user')['internal_id']);return Response::success('Email verifikasi telah dijadwalkan.',['verification_token'=>$token]); }
    public function verify(Request $r): Response { $this->service->verify((string)($r->query['token']??''));return Response::success('Email berhasil diverifikasi.'); }
    public function sessions(Request $r): Response { return Response::success('Daftar sesi berhasil diambil.',$this->repository->sessions((int)$r->attribute('user')['internal_id'])); }
    public function revokeSession(Request $r): Response { $id=(string)$r->attribute('sessionId');if(!$this->repository->revokeSession((int)$r->attribute('user')['internal_id'],$id))throw new HttpException(404,'Sesi tidak ditemukan.');return Response::success('Sesi berhasil dicabut.',null,204); }
}
