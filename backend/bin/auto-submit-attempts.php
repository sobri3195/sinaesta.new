<?php
declare(strict_types=1);
use Sinaesta\Assessment\Application\AssessmentService;use Sinaesta\Assessment\Application\ScoringService;use Sinaesta\Assessment\Infrastructure\AssessmentRepository;
$pdo=require dirname(__DIR__).'/config/bootstrap.php';$repository=new AssessmentRepository($pdo);$service=new AssessmentService($repository,new ScoringService());$failed=0;foreach($repository->expired()as$attempt){try{$service->submit($attempt['public_id'],(int)$attempt['user_id'],true);echo "submitted {$attempt['public_id']}\n";}catch(Throwable$e){$failed++;fwrite(STDERR,"failed {$attempt['public_id']}: {$e->getMessage()}\n");}}exit($failed===0?0:1);
