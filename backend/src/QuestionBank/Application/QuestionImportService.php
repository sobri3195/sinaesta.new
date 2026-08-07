<?php

declare(strict_types=1);

namespace Sinaesta\QuestionBank\Application;

use PDO;
use Sinaesta\QuestionBank\Infrastructure\QuestionRepository;
use Sinaesta\Shared\Http\HttpException;

final readonly class QuestionImportService
{
    private const HEADERS=['stem','clinical_vignette','learning_objective','difficulty','category_id','topic_id','options_json','main_explanation','references_json'];
    public function __construct(private PDO $pdo,private QuestionRepository $questions,private QuestionValidator $validator) {}

    public function preview(string $csv,string $filename,bool $atomic,array $actor,string $requestId):array
    {
        if(trim($csv)==='') throw new HttpException(422,'File CSV kosong.');
        $stream=fopen('php://temp','r+'); if($stream===false) throw new \RuntimeException('Temporary stream unavailable.');
        fwrite($stream,$csv); rewind($stream); $header=fgetcsv($stream,0,',','"','\\');
        if($header===false||array_diff(self::HEADERS,$header)!==[]) throw new HttpException(422,'Header CSV tidak valid.',['header'=>['Header wajib: '.implode(', ',self::HEADERS)]]);
        $batchId=QuestionRepository::uuid(); $actorId=(int)$actor['internal_id'];
        $this->pdo->beginTransaction();
        try {
            $insert=$this->pdo->prepare("INSERT INTO question_import_batches (public_id,uploaded_by,original_filename,header_json,status,atomic_mode,created_at) VALUES (:id,:actor,:filename,:header,'preview',:atomic,UTC_TIMESTAMP())");
            $insert->execute(['id'=>$batchId,'actor'=>$actorId,'filename'=>mb_substr($filename,0,255),'header'=>json_encode($header,JSON_THROW_ON_ERROR),'atomic'=>$atomic?1:0]); $internal=(int)$this->pdo->lastInsertId();
            $rowNumber=1; $valid=0; $invalid=0; $preview=[];
            while(($values=fgetcsv($stream,0,',','"','\\'))!==false){ ++$rowNumber; if(count($values)!==count($header)){ $payload=[]; $errors=['row'=>['Jumlah kolom tidak sesuai header.']]; } else { $payload=array_combine($header,$values); $payload=$this->decode($payload); $errors=$this->validator->validate($payload); }
                $row=$this->pdo->prepare('INSERT INTO question_import_rows (batch_id,row_number,payload_json,is_valid,created_at) VALUES (:batch,:number,:payload,:valid,UTC_TIMESTAMP())');
                $row->execute(['batch'=>$internal,'number'=>$rowNumber,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'valid'=>$errors===[]?1:0]); $rowId=(int)$this->pdo->lastInsertId();
                foreach($errors as $field=>$messages) foreach($messages as $message){$error=$this->pdo->prepare('INSERT INTO question_import_errors (import_row_id,field_name,error_code,message,created_at) VALUES (:row,:field,:code,:message,UTC_TIMESTAMP())');$error->execute(['row'=>$rowId,'field'=>$field,'code'=>'validation_error','message'=>$message]);}
                $errors===[]?++$valid:++$invalid; $preview[]=['row'=>$rowNumber,'valid'=>$errors===[],'errors'=>$errors];
            }
            $update=$this->pdo->prepare('UPDATE question_import_batches SET total_rows=:total,valid_rows=:valid,error_rows=:errors WHERE id=:id');$update->execute(['total'=>$valid+$invalid,'valid'=>$valid,'errors'=>$invalid,'id'=>$internal]);
            $this->audit($actorId,'question_import.preview',$requestId); $this->pdo->commit();
            return ['id'=>$batchId,'status'=>'preview','atomic'=>$atomic,'total_rows'=>$valid+$invalid,'valid_rows'=>$valid,'error_rows'=>$invalid,'rows'=>$preview];
        } catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;} finally {fclose($stream);}
    }

    public function confirm(string $batchId,array $actor,string $requestId):array
    {
        $this->pdo->beginTransaction();
        try {
            $statement=$this->pdo->prepare("SELECT id,atomic_mode,error_rows,status FROM question_import_batches WHERE public_id=:id AND uploaded_by=:actor FOR UPDATE");$statement->execute(['id'=>$batchId,'actor'=>$actor['internal_id']]);$batch=$statement->fetch();
            if(!$batch) throw new HttpException(404,'Batch import tidak ditemukan.'); if($batch['status']!=='preview') throw new HttpException(409,'Batch sudah dikonfirmasi.');
            if((bool)$batch['atomic_mode']&&(int)$batch['error_rows']>0) throw new HttpException(422,'Mode atomic tidak dapat mengimpor batch yang memiliki error.');
            $rows=$this->pdo->prepare('SELECT id,payload_json FROM question_import_rows WHERE batch_id=:batch AND is_valid=1 ORDER BY row_number');$rows->execute(['batch'=>$batch['id']]);$imported=0;
            foreach($rows->fetchAll() as $row){$payload=json_decode($row['payload_json'],true,32,JSON_THROW_ON_ERROR);$questionId=$this->questions->create($payload,(int)$actor['internal_id']);$find=$this->questions->find($questionId);$update=$this->pdo->prepare('UPDATE question_import_rows SET imported_question_id=:question WHERE id=:id');$update->execute(['question'=>$find['internal_id'],'id'=>$row['id']]);++$imported;}
            $update=$this->pdo->prepare("UPDATE question_import_batches SET status='imported',confirmed_at=UTC_TIMESTAMP(),imported_at=UTC_TIMESTAMP() WHERE id=:id");$update->execute(['id'=>$batch['id']]);$this->audit((int)$actor['internal_id'],'question_import.confirm',$requestId);$this->pdo->commit();
            return ['id'=>$batchId,'status'=>'imported','imported_rows'=>$imported];
        }catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
    }
    private function decode(array $row):array { foreach(['options_json'=>'options','references_json'=>'references'] as $source=>$target){try{$row[$target]=json_decode($row[$source]?:'[]',true,32,JSON_THROW_ON_ERROR);}catch(\JsonException){$row[$target]=null;}unset($row[$source]);}$row['question_type']='single_best_answer';return $row; }
    private function audit(int $actor,string $action,string $requestId):void {$s=$this->pdo->prepare("INSERT INTO audit_logs (public_id,actor_user_id,action,outcome,request_id,created_at) VALUES (:id,:actor,:action,'success',:request,UTC_TIMESTAMP())");$s->execute(['id'=>bin2hex(random_bytes(16)),'actor'=>$actor,'action'=>$action,'request'=>$requestId]);}
}
