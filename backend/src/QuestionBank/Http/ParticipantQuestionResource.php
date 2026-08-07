<?php

declare(strict_types=1);

namespace Sinaesta\QuestionBank\Http;

/** Allowlist transformer: answer keys and review content can never cross this boundary. */
final class ParticipantQuestionResource
{
    public static function from(array $question): array
    {
        return [
            'id'=>$question['id'], 'question_type'=>$question['question_type'], 'stem'=>$question['stem'],
            'clinical_vignette'=>$question['clinical_vignette'], 'difficulty'=>$question['difficulty'],
            'options'=>array_map(static fn(array $option):array=>['id'=>$option['id'],'content'=>$option['content']],$question['options']),
            'media'=>array_map(static fn(array $medium):array=>['id'=>$medium['id'],'storage_key'=>$medium['storage_key'],'mime_type'=>$medium['mime_type'],'alt_text'=>$medium['alt_text']],$question['media']),
        ];
    }
}
