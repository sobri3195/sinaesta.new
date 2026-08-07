<?php

declare(strict_types=1);

namespace Sinaesta\QuestionBank\Application;

use Sinaesta\QuestionBank\Infrastructure\QuestionRepository;
use Sinaesta\Shared\Http\HttpException;

final readonly class QuestionService
{
    public function __construct(private QuestionRepository $repository, private QuestionValidator $validator) {}

    public function list(array $query): array
    {
        $page=max(1,(int)($query['page']??1)); $limit=min(100,max(1,(int)($query['limit']??20)));
        $status=isset($query['status'])?(string)$query['status']:null;
        return ['items'=>$this->repository->list($limit,($page-1)*$limit,$status),'meta'=>['page'=>$page,'limit'=>$limit,'total'=>$this->repository->count($status)]];
    }

    public function get(string $id): array { return $this->required($id); }
    public function participant(string $id): array { if(!preg_match('/^[0-9a-f-]{36}$/i',$id))throw new HttpException(404,'Soal tidak ditemukan.'); return $this->repository->published($id)??throw new HttpException(404,'Soal tidak ditemukan.'); }

    public function create(array $data, array $actor): array
    {
        $this->validate($data);
        try {
            $id=$this->repository->transaction(fn():string=>$this->repository->create($data,(int)$actor['internal_id']));
        } catch (\DomainException $exception) {
            throw new HttpException(422,$exception->getMessage(),['topic_id'=>[$exception->getMessage()]]);
        }
        return $this->required($id);
    }

    public function update(string $id, array $patch, array $actor): array
    {
        try { return $this->repository->transaction(function() use($id,$patch,$actor):array {
            $current=$this->required($id,true); $this->assertAuthorOrAdmin($current,$actor);
            if (!in_array($current['status'],['draft','revision','published'],true)) throw new HttpException(409,'Soal pada status ini tidak dapat diedit.');
            $data=array_replace($this->editable($current),$patch); $this->validate($data);
            $version=(int)$current['current_version']+1;
            $this->repository->update((int)$current['internal_id'],$data,(int)$actor['internal_id'],$version);
            if ($current['status']==='published') $this->repository->transition((int)$current['internal_id'],'published','revision',(int)$actor['internal_id'],'Versi baru dibuat dari soal published.');
            return $this->required($id);
        }); } catch (\DomainException $exception) {
            throw new HttpException(422,$exception->getMessage(),['topic_id'=>[$exception->getMessage()]]);
        }
    }

    public function transition(string $id, string $action, array $actor, ?string $note): array
    {
        return $this->repository->transaction(function() use($id,$action,$actor,$note):array {
            $q=$this->required($id,true); $actorId=(int)$actor['internal_id'];
            $map=['submit-review'=>[['draft','revision'],'in_review'],'publish'=>[['approved'],'published'],'archive'=>[['draft','revision','approved','published'],'archived'],'restore'=>[['archived'],'draft']];
            if ($action==='approve'||$action==='request-revision') {
                $this->assertReviewer($actor);
                if ((int)$q['author_user_id']===$actorId) throw new HttpException(403,'Author tidak dapat mereview atau menyetujui soal sendiri.');
                if ($q['status']!=='in_review') throw new HttpException(409,'Soal tidak sedang direview.');
                if ($action==='approve') $this->validate($this->editable($q),true);
                if ($action==='request-revision'&&trim((string)$note)==='') throw new HttpException(422,'Catatan revisi wajib diisi.',['note'=>['Field wajib diisi.']]);
                $to=$action==='approve'?'approved':'revision'; $decision=$action==='approve'?'approved':'revision';
                $this->repository->review((int)$q['internal_id'],(int)$q['current_version'],$actorId,$decision,$note);
                $this->repository->transition((int)$q['internal_id'],'in_review',$to,$actorId,$note,$actorId);
            } else {
                if (!isset($map[$action])) throw new HttpException(400,'Aksi workflow tidak valid.');
                [$allowed,$to]=$map[$action];
                if (!in_array($q['status'],$allowed,true)) throw new HttpException(409,'Transisi status tidak valid.');
                if ($action==='submit-review') $this->assertAuthorOrAdmin($q,$actor);
                else $this->assertAdmin($actor);
                if ($action==='publish') $this->validate($this->editable($q),true,true);
                $this->repository->transition((int)$q['internal_id'],$q['status'],$to,$actorId,$note);
            }
            return $this->required($id);
        });
    }

    public function duplicate(string $id,array $actor):array
    {
        $source=$this->required($id); $data=$this->editable($source); $data['stem']='Salinan - '.$data['stem'];
        return $this->create($data,$actor);
    }

    public function related(string $id,string $kind):array
    {
        $q=$this->required($id); $internal=(int)$q['internal_id'];
        return match($kind){'versions'=>$this->repository->versions($internal),'reviews'=>$this->repository->reviews($internal),'history'=>$this->repository->historyList($internal),default=>throw new HttpException(400,'Resource tidak valid.')};
    }

    private function required(string $id,bool $lock=false):array
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',$id)) throw new HttpException(404,'Soal tidak ditemukan.');
        return $this->repository->find($id,$lock)??throw new HttpException(404,'Soal tidak ditemukan.');
    }
    private function editable(array $q):array { return ['question_type'=>$q['question_type'],'stem'=>$q['stem'],'clinical_vignette'=>$q['clinical_vignette'],'main_explanation'=>$q['main_explanation'],'learning_objective'=>$q['learning_objective'],'category_id'=>$q['category_id'],'topic_id'=>$q['topic_id'],'difficulty'=>$q['difficulty'],'options'=>$q['options'],'references'=>$q['references'],'media'=>$q['media'],'tag_ids'=>array_column($q['tags'],'id')]; }
    private function validate(array $data,bool $approval=false,bool $publish=false):void { $errors=$this->validator->validate($data,$approval,$publish); if($errors!==[]) throw new HttpException(422,'Validasi soal gagal.',$errors); }
    private function assertAuthorOrAdmin(array $q,array $actor):void { if((int)$q['author_user_id']!==(int)$actor['internal_id']&&!in_array('admin',$actor['roles']??[],true)) throw new HttpException(403,'Hanya author atau admin yang dapat mengubah soal.'); }
    private function assertReviewer(array $actor):void { if(array_intersect(['reviewer','admin'],$actor['roles']??[])===[]) throw new HttpException(403,'Role reviewer diperlukan.'); }
    private function assertAdmin(array $actor):void { if(!in_array('admin',$actor['roles']??[],true)) throw new HttpException(403,'Role admin diperlukan.'); }
}
