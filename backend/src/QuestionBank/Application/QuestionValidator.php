<?php

declare(strict_types=1);

namespace Sinaesta\QuestionBank\Application;

final class QuestionValidator
{
    public const ACTIVE_TYPE = 'single_best_answer';
    private const DIFFICULTIES = ['easy', 'medium', 'hard'];
    private const MEDIA_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    /** @return array<string,list<string>> */
    public function validate(array $data, bool $forApproval = false, bool $forPublish = false): array
    {
        $errors = [];
        $required = static function (string $field) use ($data, &$errors): void {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                $errors[$field][] = 'Field wajib diisi.';
            }
        };
        foreach (['stem', 'learning_objective', 'category_id', 'topic_id', 'difficulty'] as $field) {
            $required($field);
        }
        if (($data['question_type'] ?? self::ACTIVE_TYPE) !== self::ACTIVE_TYPE) {
            $errors['question_type'][] = 'Tipe soal belum aktif pada MVP.';
        }
        if (isset($data['difficulty']) && !in_array($data['difficulty'], self::DIFFICULTIES, true)) {
            $errors['difficulty'][] = 'Difficulty harus easy, medium, atau hard.';
        }
        $options = $data['options'] ?? null;
        if (!is_array($options) || count($options) < 2) {
            $errors['options'][] = 'Minimal dua option wajib tersedia.';
        } else {
            $seen = [];
            $correct = 0;
            foreach (array_values($options) as $index => $option) {
                $content = is_array($option) && is_string($option['content'] ?? null) ? trim($option['content']) : '';
                if ($content === '') {
                    $errors["options.{$index}.content"][] = 'Option tidak boleh kosong.';
                    continue;
                }
                $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', $content) ?? $content);
                if (isset($seen[$normalized])) {
                    $errors['options'][] = 'Option tidak boleh identik.';
                }
                $seen[$normalized] = true;
                if (($option['is_correct'] ?? false) === true) {
                    ++$correct;
                }
            }
            if ($correct !== 1) {
                $errors['options'][] = 'Harus tepat satu correct option.';
            }
        }
        if ($forApproval) {
            $required('main_explanation');
        }
        if ($forPublish) {
            $references = $data['references'] ?? [];
            if (!is_array($references) || $references === []) {
                $errors['references'][] = 'Reference wajib sebelum publish.';
            }
        }
        foreach (($data['references'] ?? []) as $index => $reference) {
            $year = filter_var($reference['year'] ?? null, FILTER_VALIDATE_INT);
            if ($year === false || $year < 1800 || $year > ((int) gmdate('Y') + 1)) {
                $errors["references.{$index}.year"][] = 'Tahun referensi tidak valid.';
            }
        }
        foreach (($data['media'] ?? []) as $index => $medium) {
            if (!is_array($medium) || !in_array($medium['mime_type'] ?? '', self::MEDIA_TYPES, true)) {
                $errors["media.{$index}.mime_type"][] = 'MIME type tidak diizinkan.';
            }
        }
        return $errors;
    }
}
