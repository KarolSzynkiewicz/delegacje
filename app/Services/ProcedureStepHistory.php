<?php

namespace App\Services;

use App\Enums\ApprovalDecision;
use App\Enums\RecruitmentCandidateFlag;
use App\Enums\RecruitmentStatus;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Payroll;
use App\Models\ProcedureRunStep;
use App\Models\Role;
use App\ProcedureActions\ActionCatalog;
use RuntimeException;

class ProcedureStepHistory
{
    /**
     * Krótki opis tego, co faktycznie stało się w kroku.
     *
     * @return array{text: string, url: string|null, tone: string|null}|null
     */
    public static function outcome(ProcedureRunStep $step): ?array
    {
        return match ($step->node_type) {
            'decision' => self::decision($step),
            'approval' => self::approval($step),
            'comment' => self::comment($step),
            'action' => self::action($step),
            'checklist' => self::checklist($step),
            'wait' => self::wait($step),
            default => null,
        };
    }

    /** @return array{text: string, url: string|null, tone: string|null}|null */
    private static function decision(ProcedureRunStep $step): ?array
    {
        $data = self::data($step);
        $label = trim((string) ($data['label'] ?? ''));
        if ($label === '') {
            $label = trim((string) ($data['option_id'] ?? ''));
        }

        if ($label === '') {
            return null;
        }

        return self::pack('Wybrano: '.$label);
    }

    /** @return array{text: string, url: string|null, tone: string|null}|null */
    private static function approval(ProcedureRunStep $step): ?array
    {
        $data = self::data($step);
        $raw = (string) ($data['approval_decision'] ?? $data['option_id'] ?? '');
        $decision = ApprovalDecision::tryFrom($raw);
        if ($decision === null) {
            return $raw !== '' ? self::pack($raw) : null;
        }

        return self::pack(
            $decision->label(),
            null,
            $decision === ApprovalDecision::Approved ? 'ok' : 'no',
        );
    }

    /** @return array{text: string, url: string|null, tone: string|null}|null */
    private static function comment(ProcedureRunStep $step): ?array
    {
        $data = self::data($step);
        $body = trim((string) ($data['body'] ?? ''));
        if ($body === '') {
            return null;
        }

        $url = null;
        $commentId = (int) ($data['comment_id'] ?? 0);
        if ($commentId > 0) {
            $comment = Comment::query()->find($commentId);
            $url = $comment?->urlWithCommentAnchor();
        }

        return self::pack('„'.$body.'”', $url);
    }

    /** @return array{text: string, url: string|null, tone: string|null}|null */
    private static function wait(ProcedureRunStep $step): ?array
    {
        $data = self::data($step);

        return self::pack(! empty($data['wait_elapsed'])
            ? 'Czas minął — procedura ruszyła sama.'
            : 'Kontynuowano wcześniej.');
    }

    /** @return array{text: string, url: string|null, tone: string|null}|null */
    private static function checklist(ProcedureRunStep $step): ?array
    {
        $data = $step->data;
        if (! is_array($data) || $data === []) {
            return null;
        }

        $items = array_is_list($data) ? $data : [];
        if ($items === []) {
            return null;
        }

        $checked = 0;
        foreach ($items as $item) {
            if (is_array($item) && ! empty($item['checked'])) {
                $checked++;
            }
        }

        return self::pack('Zaznaczono '.$checked.' z '.count($items).' pozycji.');
    }

    /** @return array{text: string, url: string|null, tone: string|null}|null */
    private static function action(ProcedureRunStep $step): ?array
    {
        $data = self::data($step);
        if ($data === []) {
            return null;
        }

        $actionKey = (string) ($data['action'] ?? '');
        $result = is_array($data['result'] ?? null) ? $data['result'] : [];
        $bits = [];

        $label = self::actionLabel($actionKey);
        if ($label !== null) {
            $bits[] = $label;
        }

        if (array_key_exists('average', $result) && $result['average'] !== null && $result['average'] !== '') {
            $bits[] = 'średnia '.self::scalar($result['average']);
        }

        if (! empty($result['status'])) {
            $status = RecruitmentStatus::tryFrom((string) $result['status']);
            $bits[] = 'status: '.($status?->label() ?? (string) $result['status']);
        }

        if ($actionKey === 'recruitment.hire' && ! empty($result['employee_id'])) {
            $bits[] = 'utworzono kartę pracownika';
        }

        foreach (self::orderedActionFields($data) as $key => $value) {
            $formatted = self::formatField((string) $key, $value, $data);
            if ($formatted !== null) {
                $bits[] = $formatted;
            }
        }

        return $bits === [] ? null : self::pack(implode(' · ', $bits));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function orderedActionFields(array $data): array
    {
        $preferred = [
            'engagement', 'skills', 'orderliness', 'behavior',
            'amount', 'currency', 'start_date', 'end_date', 'date',
            'company_id', 'payroll_id', 'roles', 'flag',
            'notes', 'note',
        ];

        $ordered = [];
        foreach ($preferred as $key) {
            if (array_key_exists($key, $data)) {
                $ordered[$key] = $data[$key];
            }
        }

        foreach ($data as $key => $value) {
            if (in_array($key, ['action', 'result', 'comment_id'], true) || array_key_exists($key, $ordered)) {
                continue;
            }
            $ordered[$key] = $value;
        }

        return $ordered;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function formatField(string $key, mixed $value, array $data): ?string
    {
        if ($key === 'currency' && array_key_exists('amount', $data)) {
            return null;
        }

        if ($key === 'amount') {
            $amount = self::scalar($value);
            if ($amount === '') {
                return null;
            }
            $currency = strtoupper(trim((string) ($data['currency'] ?? 'PLN')));

            return 'kwota '.$amount.($currency !== '' ? ' '.$currency : '');
        }

        if ($key === 'roles') {
            $names = self::roleNames($value);

            return $names === [] ? null : 'role: '.implode(', ', $names);
        }

        if ($key === 'company_id') {
            $name = Company::query()->whereKey((int) $value)->value('name');

            return $name ? 'spółka: '.$name : null;
        }

        if ($key === 'payroll_id') {
            $payroll = Payroll::query()->find((int) $value);

            return $payroll ? 'lista płac: '.$payroll->display_name : null;
        }

        if ($key === 'flag') {
            $flag = RecruitmentCandidateFlag::tryFrom((string) $value);

            return 'ocena: '.($flag?->label() ?? (string) $value);
        }

        $text = self::scalar($value);
        if ($text === '') {
            return null;
        }

        $label = match ($key) {
            'engagement' => 'zaangażowanie',
            'skills' => 'umiejętności',
            'orderliness' => 'porządek',
            'behavior' => 'zachowanie',
            'notes' => 'uwagi',
            'note' => 'uzasadnienie',
            'start_date' => 'od',
            'end_date' => 'do',
            'date' => 'data',
            default => $key,
        };

        if (in_array($key, ['notes', 'note'], true)) {
            return $label.': '.$text;
        }

        return $label.' '.$text;
    }

    /** @return list<string> */
    private static function roleNames(mixed $value): array
    {
        $ids = array_values(array_filter(array_map('intval', (array) $value)));
        if ($ids === []) {
            return [];
        }

        return Role::query()->whereIn('id', $ids)->orderBy('name')->pluck('name')->all();
    }

    private static function actionLabel(string $key): ?string
    {
        if ($key === '') {
            return null;
        }

        try {
            return app(ActionCatalog::class)->find($key)->label();
        } catch (RuntimeException) {
            return $key;
        }
    }

    private static function scalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'tak' : 'nie';
        }

        if (is_array($value)) {
            return '';
        }

        return trim((string) $value);
    }

    /** @return array<string, mixed> */
    private static function data(ProcedureRunStep $step): array
    {
        $data = $step->data;

        return is_array($data) && ! array_is_list($data) ? $data : [];
    }

    /**
     * @return array{text: string, url: string|null, tone: string|null}
     */
    private static function pack(string $text, ?string $url = null, ?string $tone = null): array
    {
        return [
            'text' => $text,
            'url' => $url,
            'tone' => $tone,
        ];
    }
}
