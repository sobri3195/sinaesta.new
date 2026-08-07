<?php

declare(strict_types=1);

namespace Sinaesta\QuestionBank\Infrastructure;

use PDO;

final readonly class QuestionRepository
{
    public function __construct(private PDO $pdo) {}

    public function transaction(callable $operation): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $operation();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** @return list<array<string,mixed>> */
    public function list(int $limit, int $offset, ?string $status): array
    {
        $where = $status === null ? '' : ' WHERE q.status=:status';
        $sql = 'SELECT q.public_id id,q.question_type,q.stem,q.difficulty,q.status,q.current_version,q.published_at,q.created_at,q.updated_at,c.public_id category_id,c.name category_name,t.public_id topic_id,t.name topic_name,u.public_id author_id,u.name author_name FROM questions q JOIN question_categories c ON c.id=q.category_id JOIN question_topics t ON t.id=q.topic_id JOIN users u ON u.id=q.author_user_id' . $where . ' ORDER BY q.updated_at DESC LIMIT :limit OFFSET :offset';
        $statement = $this->pdo->prepare($sql);
        if ($status !== null) $statement->bindValue(':status', $status);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function count(?string $status): int
    {
        $sql = 'SELECT COUNT(*) FROM questions' . ($status === null ? '' : ' WHERE status=:status');
        $statement = $this->pdo->prepare($sql);
        $statement->execute($status === null ? [] : ['status' => $status]);
        return (int) $statement->fetchColumn();
    }

    public function find(string $publicId, bool $lock = false): ?array
    {
        $sql = 'SELECT q.id internal_id,q.public_id id,q.question_type,q.stem,q.clinical_vignette,q.main_explanation,q.learning_objective,q.difficulty,q.status,q.current_version,q.published_at,q.created_at,q.updated_at,q.author_user_id,q.reviewer_user_id,c.public_id category_id,t.public_id topic_id FROM questions q JOIN question_categories c ON c.id=q.category_id JOIN question_topics t ON t.id=q.topic_id WHERE q.public_id=:id' . ($lock ? ' FOR UPDATE' : '');
        $statement = $this->pdo->prepare($sql); $statement->execute(['id' => $publicId]);
        $question = $statement->fetch();
        if (!$question) return null;
        $question['options'] = $this->children('SELECT public_id id,content,is_correct,explanation FROM question_options WHERE question_id=:id ORDER BY option_order', (int) $question['internal_id']);
        foreach ($question['options'] as &$option) $option['is_correct'] = (bool) $option['is_correct'];
        $question['references'] = $this->children('SELECT public_id id,citation,reference_year year,url FROM question_references WHERE question_id=:id ORDER BY id', (int) $question['internal_id']);
        $question['media'] = $this->children('SELECT public_id id,storage_key,mime_type,alt_text FROM question_media WHERE question_id=:id ORDER BY id', (int) $question['internal_id']);
        $question['tags'] = $this->children('SELECT t.public_id id,t.name FROM question_tags t JOIN question_tag_relations r ON r.tag_id=t.id WHERE r.question_id=:id ORDER BY t.name', (int) $question['internal_id']);
        return $question;
    }

    private function children(string $sql, int $id): array
    {
        $statement = $this->pdo->prepare($sql); $statement->execute(['id' => $id]); return $statement->fetchAll();
    }

    public function create(array $data, int $actorId): string
    {
        $id = self::uuid();
        [$category, $topic] = $this->taxonomy($data['category_id'], $data['topic_id']);
        $statement = $this->pdo->prepare("INSERT INTO questions (public_id,question_type,stem,clinical_vignette,main_explanation,learning_objective,category_id,topic_id,difficulty,author_user_id,status,created_at,updated_at) VALUES (:id,'single_best_answer',:stem,:vignette,:explanation,:objective,:category,:topic,:difficulty,:author,'draft',UTC_TIMESTAMP(),UTC_TIMESTAMP())");
        $statement->execute(['id'=>$id,'stem'=>trim($data['stem']),'vignette'=>$data['clinical_vignette'] ?? null,'explanation'=>$data['main_explanation'] ?? null,'objective'=>trim($data['learning_objective']),'category'=>$category,'topic'=>$topic,'difficulty'=>$data['difficulty'],'author'=>$actorId]);
        $internalId = (int) $this->pdo->lastInsertId();
        $this->replaceChildren($internalId, $data);
        $this->history($internalId, null, 'draft', $actorId, null);
        $this->snapshot($internalId, 1, $actorId);
        return $id;
    }

    public function update(int $id, array $data, int $actorId, int $version): void
    {
        [$category, $topic] = $this->taxonomy($data['category_id'], $data['topic_id']);
        $statement = $this->pdo->prepare('UPDATE questions SET stem=:stem,clinical_vignette=:vignette,main_explanation=:explanation,learning_objective=:objective,category_id=:category,topic_id=:topic,difficulty=:difficulty,current_version=:version,updated_at=UTC_TIMESTAMP() WHERE id=:id');
        $statement->execute(['stem'=>trim($data['stem']),'vignette'=>$data['clinical_vignette'] ?? null,'explanation'=>$data['main_explanation'] ?? null,'objective'=>trim($data['learning_objective']),'category'=>$category,'topic'=>$topic,'difficulty'=>$data['difficulty'],'version'=>$version,'id'=>$id]);
        $this->replaceChildren($id, $data);
        $this->snapshot($id, $version, $actorId);
    }

    private function taxonomy(string $categoryId, string $topicId): array
    {
        $statement = $this->pdo->prepare('SELECT c.id category_id,t.id topic_id FROM question_categories c JOIN question_topics t ON t.category_id=c.id WHERE c.public_id=:category AND t.public_id=:topic');
        $statement->execute(['category'=>$categoryId,'topic'=>$topicId]); $row=$statement->fetch();
        if (!$row) throw new \DomainException('Category dan topic tidak valid atau tidak berelasi.');
        return [(int)$row['category_id'],(int)$row['topic_id']];
    }

    private function replaceChildren(int $questionId, array $data): void
    {
        foreach (['question_options','question_references','question_media','question_tag_relations'] as $table) {
            $statement=$this->pdo->prepare("DELETE FROM {$table} WHERE question_id=:id"); $statement->execute(['id'=>$questionId]);
        }
        $option=$this->pdo->prepare('INSERT INTO question_options (public_id,question_id,option_order,content,is_correct,explanation,created_at,updated_at) VALUES (:uuid,:question,:position,:content,:correct,:explanation,UTC_TIMESTAMP(),UTC_TIMESTAMP())');
        foreach (array_values($data['options']) as $position=>$item) $option->execute(['uuid'=>self::uuid(),'question'=>$questionId,'position'=>$position+1,'content'=>trim($item['content']),'correct'=>($item['is_correct']??false)?1:0,'explanation'=>$item['explanation']??null]);
        $reference=$this->pdo->prepare('INSERT INTO question_references (public_id,question_id,citation,reference_year,url,created_at,updated_at) VALUES (:uuid,:question,:citation,:year,:url,UTC_TIMESTAMP(),UTC_TIMESTAMP())');
        foreach (($data['references']??[]) as $item) $reference->execute(['uuid'=>self::uuid(),'question'=>$questionId,'citation'=>$item['citation'],'year'=>$item['year'],'url'=>$item['url']??null]);
        $media=$this->pdo->prepare('INSERT INTO question_media (public_id,question_id,storage_key,mime_type,alt_text,created_at) VALUES (:uuid,:question,:key,:mime,:alt,UTC_TIMESTAMP())');
        foreach (($data['media']??[]) as $item) $media->execute(['uuid'=>self::uuid(),'question'=>$questionId,'key'=>$item['storage_key'],'mime'=>$item['mime_type'],'alt'=>$item['alt_text']??'']);
        $tag=$this->pdo->prepare('INSERT INTO question_tag_relations (question_id,tag_id,created_at) SELECT :question,id,UTC_TIMESTAMP() FROM question_tags WHERE public_id=:tag');
        foreach (($data['tag_ids']??[]) as $tagId) $tag->execute(['question'=>$questionId,'tag'=>$tagId]);
    }

    public function transition(int $id, string $from, string $to, int $actor, ?string $note, ?int $reviewer = null): void
    {
        $statement=$this->pdo->prepare('UPDATE questions SET status=:status,reviewer_user_id=COALESCE(:reviewer,reviewer_user_id),published_at=IF(:status="published",UTC_TIMESTAMP(),published_at),updated_at=UTC_TIMESTAMP() WHERE id=:id AND status=:from');
        $statement->execute(['status'=>$to,'reviewer'=>$reviewer,'id'=>$id,'from'=>$from]);
        if ($statement->rowCount() !== 1) throw new \DomainException('Status soal telah berubah.');
        $version=$this->pdo->prepare('UPDATE question_versions v JOIN questions q ON q.id=v.question_id AND q.current_version=v.version_number SET v.status=:status,v.published_at=IF(:status="published",UTC_TIMESTAMP(),v.published_at) WHERE v.question_id=:id');
        $version->execute(['status'=>$to,'id'=>$id]);
        if ($to === 'archived') {
            $published=$this->pdo->prepare("UPDATE question_versions SET status='archived' WHERE question_id=:id AND status='published'");
            $published->execute(['id'=>$id]);
        }
        $this->history($id,$from,$to,$actor,$note);
    }

    public function review(int $id, int $version, int $reviewer, string $decision, ?string $note): void
    {
        $statement=$this->pdo->prepare('INSERT INTO question_reviews (public_id,question_id,version_number,reviewer_user_id,decision,note,created_at) VALUES (:uuid,:id,:version,:reviewer,:decision,:note,UTC_TIMESTAMP())');
        $statement->execute(['uuid'=>self::uuid(),'id'=>$id,'version'=>$version,'reviewer'=>$reviewer,'decision'=>$decision,'note'=>$note]);
    }

    private function history(int $id, ?string $from, string $to, int $actor, ?string $note): void
    {
        $statement=$this->pdo->prepare('INSERT INTO question_status_histories (public_id,question_id,from_status,to_status,actor_user_id,note,created_at) VALUES (:uuid,:id,:from,:to,:actor,:note,UTC_TIMESTAMP())');
        $statement->execute(['uuid'=>self::uuid(),'id'=>$id,'from'=>$from,'to'=>$to,'actor'=>$actor,'note'=>$note]);
    }

    private function snapshot(int $id, int $version, int $actor): void
    {
        $statement=$this->pdo->prepare('SELECT q.question_type,q.stem,q.clinical_vignette,q.main_explanation,q.learning_objective,q.difficulty,c.public_id category_id,t.public_id topic_id FROM questions q JOIN question_categories c ON c.id=q.category_id JOIN question_topics t ON t.id=q.topic_id WHERE q.id=:id'); $statement->execute(['id'=>$id]); $snapshot=$statement->fetch();
        $snapshot['options']=$this->children('SELECT public_id id,content,is_correct,explanation FROM question_options WHERE question_id=:id ORDER BY option_order',$id);
        $snapshot['references']=$this->children('SELECT public_id id,citation,reference_year year,url FROM question_references WHERE question_id=:id ORDER BY id',$id);
        $snapshot['media']=$this->children('SELECT public_id id,storage_key,mime_type,alt_text FROM question_media WHERE question_id=:id ORDER BY id',$id);
        $snapshot['tags']=$this->children('SELECT t.public_id id,t.name FROM question_tags t JOIN question_tag_relations r ON r.tag_id=t.id WHERE r.question_id=:id ORDER BY t.name',$id);
        $insert=$this->pdo->prepare('INSERT INTO question_versions (public_id,question_id,version_number,snapshot_json,created_by,created_at) VALUES (:uuid,:id,:version,:snapshot,:actor,UTC_TIMESTAMP())');
        $insert->execute(['uuid'=>self::uuid(),'id'=>$id,'version'=>$version,'snapshot'=>json_encode($snapshot,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'actor'=>$actor]);
    }

    public function archive(int $id, string $from, int $actor): void { $this->transition($id,$from,'archived',$actor,null); }
    public function versions(int $id): array { return $this->children('SELECT public_id id,version_number,status,published_at,snapshot_json snapshot,created_at FROM question_versions WHERE question_id=:id ORDER BY version_number DESC',$id); }
    public function reviews(int $id): array { return $this->children('SELECT r.public_id id,r.version_number,r.decision,r.note,r.created_at,u.public_id reviewer_id,u.name reviewer_name FROM question_reviews r JOIN users u ON u.id=r.reviewer_user_id WHERE r.question_id=:id ORDER BY r.created_at DESC',$id); }
    public function historyList(int $id): array { return $this->children('SELECT h.public_id id,h.from_status,h.to_status,h.note,h.created_at,u.public_id actor_id,u.name actor_name FROM question_status_histories h JOIN users u ON u.id=h.actor_user_id WHERE h.question_id=:id ORDER BY h.created_at DESC',$id); }
    public function published(string $publicId): ?array { $s=$this->pdo->prepare("SELECT v.snapshot_json FROM question_versions v JOIN questions q ON q.id=v.question_id WHERE q.public_id=:id AND v.status='published' ORDER BY v.version_number DESC LIMIT 1");$s->execute(['id'=>$publicId]);$json=$s->fetchColumn();if(!is_string($json))return null;$data=json_decode($json,true,32,JSON_THROW_ON_ERROR);$data['id']=$publicId;$data['status']='published';return $data; }
    public static function uuid(): string { $b=random_bytes(16); $b[6]=chr((ord($b[6])&15)|64); $b[8]=chr((ord($b[8])&63)|128); return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4)); }
}
