<?php
declare(strict_types=1);
namespace Sinaesta\Shared\Http;
use PDO;
final readonly class HealthController
{
    public function __construct(private PDO $pdo) {}
    public function health(Request $r): Response { return Response::success('Aplikasi berjalan.',['application'=>'ok']); }
    public function live(Request $r): Response { return Response::success('Aplikasi hidup.',['application'=>'ok']); }
    public function ready(Request $r): Response { $checks=['application'=>'ok','database'=>'unavailable','storage'=>'unavailable','cron'=>'unavailable','mail'=>'unconfigured']; try{$this->pdo->query('SELECT 1');$checks['database']='ok';}catch(\PDOException){$checks['database']='unavailable';} $storage=getenv('STORAGE_PATH')?:dirname(__DIR__,3).'/storage';if(is_dir($storage)&&is_writable($storage))$checks['storage']='ok';$heartbeat=getenv('CRON_HEARTBEAT_FILE')?:'';if($heartbeat!==''&&is_file($heartbeat)&&filemtime($heartbeat)!==false&&filemtime($heartbeat)>time()-900)$checks['cron']='ok';if((getenv('MAIL_FROM')?:'')!=='')$checks['mail']='configured';$ready=!in_array('unavailable',$checks,true)&&$checks['mail']==='configured';return $ready?Response::success('Aplikasi siap.',$checks):Response::error('Aplikasi belum siap.',503,['checks'=>array_map(static fn($v):array=>[$v],$checks)]); }
}
