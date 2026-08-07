<?php
declare(strict_types=1);
namespace Sinaesta\Assessment\Application;

final class ScoringService
{
    /** @param list<array<string,mixed>> $rows @return array<string,mixed> */
    public function calculate(array $rows, int $duration): array
    {
        $total=count($rows); $answered=0; $correct=0; $groups=[];
        foreach($rows as $row){$isAnswered=$row['selected_option_id']!==null;$isCorrect=$isAnswered&&$row['selected_option_id']===$row['correct_option_id'];$answered+=(int)$isAnswered;$correct+=(int)$isCorrect;
            foreach(['topic','category','difficulty'] as $type){$key=(string)$row[$type];$index=$type.'|'.$key;$groups[$index]??=['dimension_type'=>$type,'dimension_key'=>$key,'total'=>0,'correct'=>0,'incorrect'=>0,'unanswered'=>0];$groups[$index]['total']++;$groups[$index][$isCorrect?'correct':($isAnswered?'incorrect':'unanswered')]++;}
        }
        $incorrect=$answered-$correct;$unanswered=$total-$answered;
        foreach($groups as &$group)$group['percentage']=$group['total']===0?0:round(100*$group['correct']/$group['total'],2);
        return ['total_questions'=>$total,'answered'=>$answered,'unanswered'=>$unanswered,'correct'=>$correct,'incorrect'=>$incorrect,'raw_score'=>$correct,'percentage_score'=>$total===0?0:round(100*$correct/$total,2),'duration_seconds'=>max(0,$duration),'average_time_seconds'=>$total===0?0:round(max(0,$duration)/$total,2),'analytics'=>array_values($groups)];
    }
}
