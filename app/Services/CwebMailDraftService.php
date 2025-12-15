<?php

namespace App\Services;

use App\Models\CwebCase;
use App\Models\User;

class CwebMailDraftService
{
    public function subject(string $type, CwebCase $case): string
    {
        $no = $case->manage_no ?? '';
        return match ($type) {
            'registration' => "Registration_顧客要求の新規登録がありました / C-WEB /({$no})",
            'abolition'    => "Abolition_顧客要求が廃止されました / C-WEB /({$no})",
            default        => "New Comment コメントが投稿されました / C-WEB /({$no})",
        };
    }

    public function body(string $type, CwebCase $case, string $caseUrl, ?string $commentBody = null): string
    {
        return match ($type) {
            'registration', 'abolition' => $caseUrl,
            default => trim(
                ($commentBody ? "【コメント】\n{$commentBody}\n\n" : '') .
                "【案件URL】\n{$caseUrl}"
            ),
        };
    }

    /**
     * 登録/廃止 の送信先（品証マスタ + 営業窓口 + 情報共有者 + その他要求対応者 + 製品担当）
     * -> “社員番号”で集約して、最後に email に変換
     */
    public function defaultRecipientEmails(CwebCase $case): array
    {
        $case->loadMissing(['sharedUsers.user', 'otherRequirements']);

        $empNos = collect();

        // 品証マスタ
        $empNos = $empNos->merge((array)config('cweb.qa_master_empnos', []));

        // 営業窓口
        if (!empty($case->sales_contact_employee_number)) {
            $empNos->push($case->sales_contact_employee_number);
        }

        // 情報共有者
        foreach (($case->sharedUsers ?? []) as $row) {
            if (($row->role ?? null) === 'shared') {
                $empNos->push(optional($row->user)->employee_number);
            }
        }

        // その他要求対応者
        foreach (($case->otherRequirements ?? []) as $req) {
            $empNos->push($req->responsible_employee_number ?? null);
        }

        // 製品担当
        $pg = $case->product_group ?? null;
        $pc = $case->product_code ?? null;
        $map = (array)config('cweb.product_owners_map', []);
        if ($pg && $pc && isset($map[$pg][$pc])) {
            $empNos = $empNos->merge($map[$pg][$pc]);
        }

        $empNos = $empNos->filter()->unique()->values()->all();

        // 社員番号 → email
        return User::whereIn('employee_number', $empNos)
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * “選択された送信先”用：社員番号配列 → email 配列
     */
    public function selectedRecipientEmails(array $empNos): array
    {
        $empNos = collect($empNos)->filter()->unique()->values()->all();

        return User::whereIn('employee_number', $empNos)
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function buildMailto(array $toEmails, string $subject, string $body): string
    {
        $to = implode(',', $toEmails);

        // mailto はURLエンコード必須
        $qs = http_build_query([
            'subject' => $subject,
            'body'    => $body,
        ]);

        return "mailto:{$to}?{$qs}";
    }
}
