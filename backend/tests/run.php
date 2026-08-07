<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use Sinaesta\Shared\Http\Request; use Sinaesta\Shared\Http\Response; use Sinaesta\Shared\Http\Router;
$failures=[];
$assert=static function(bool $condition,string $message) use (&$failures): void { if(!$condition)$failures[]=$message; };
$router=new Router();
$router->group('/api/v1',static function(Router $router): void { $router->get('/items/{id}',static fn(Request $r): Response=>Response::success('ok',['id'=>$r->attribute('id')]));$router->post('/items',static fn(Request $r): Response=>Response::success('created',null,201)); });
$found=$router->dispatch(new Request('GET','/api/v1/items/abc%20123'));
$assert($found->status===200 && $found->payload['data']['id']==='abc 123','router matches and decodes route parameters');
$assert($router->dispatch(new Request('DELETE','/api/v1/items/1'))->status===405,'router returns 405 for matched path with invalid method');
$assert($router->dispatch(new Request('GET','/missing'))->status===404,'router returns 404 for unknown path');
$error=Response::error('Validasi gagal',422,['email'=>['Email wajib diisi.']]);
$assert($error->payload['success']===false && $error->payload['errors']['email'][0]==='Email wajib diisi.','error response follows envelope');
if($failures!==[]){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}\n");exit(1);} echo "4 tests passed\n";
