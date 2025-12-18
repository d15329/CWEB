<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CwebCase;
use App\Models\CwebQualityMaster;
use App\Models\CwebProductOwner;
use App\Models\CwebCaseSharedUser;
use App\Models\CwebCaseOtherRequirement;

class CwebCasePolicy
{
    /**
     * 社員番号の正規化（非数字除去 → 先頭0除去）
     */
    private function norm(?string $v): string
    {
        $v = (string)$v;
        $v = preg_replace('/\D+/', '', $v); // 数字以外を削除
        $v = ltrim($v, '0');                // 先頭0削除
        return $v === '' ? '0' : $v;
    }

    /**
     * PostgreSQL用 正規化式（非数字除去 → 先頭0除去）
     * 例: ltrim(regexp_replace(cast(employee_number as text), '\D', '', 'g'), '0')
     */
    private function pgNormExpr(string $col): string
    {
        return "ltrim(regexp_replace(cast({$col} as text), '\\\\D', '', 'g'), '0')";
    }

    /** 新規登録できるか（品証担当のみ） */
    public function create(User $user): bool
    {
        $me = $this->norm($user->employee_number ?? '');

        return CwebQualityMaster::query()
            ->whereRaw($this->pgNormExpr('employee_number') . " = ?", [$me])
            ->exists();
    }

    /** 案件詳細を見れるか */
    public function view(User $user, CwebCase $case): bool
    {
        $me = $this->norm($user->employee_number ?? '');

        // 1) 品証担当：全案件
        $isQuality = CwebQualityMaster::query()
            ->whereRaw($this->pgNormExpr('employee_number') . " = ?", [$me])
            ->exists();
        if ($isQuality) return true;

        // 2) 製品担当：担当製品の全案件（product_group/product_code が一致）
        $isProductOwner = CwebProductOwner::query()
            ->whereRaw($this->pgNormExpr('employee_number') . " = ?", [$me])
            ->where('product_group', $case->product_group)
            ->where('product_code',  $case->product_code)
            ->exists();
        if ($isProductOwner) return true;

        // 3) 営業窓口：指定された案件（案件の sales_contact_employee_number が一致）
        if ($this->norm($case->sales_contact_employee_number ?? '') === $me) {
            return true;
        }

        // 4) 情報共有者：指定された案件（共有テーブルにいる）
        // ★ここが今回の肝：cweb_case_shared_users に employee_number が無いので user_id で判定する
        $isShared = CwebCaseSharedUser::query()
            ->where('case_id', $case->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($isShared) return true;

        // 5) 「その他要求」対応者も見せたい場合（テーブル設計に合わせてどちらか）
        // (A) other_requirements 側に responder_user_id がある場合
        // $isOtherReq = CwebCaseOtherRequirement::query()
        //     ->where('case_id', $case->id)
        //     ->where('responder_user_id', $user->id)
        //     ->exists();
        // if ($isOtherReq) return true;

        // (B) other_requirements 側に responder_employee_number がある場合（先頭0/非数字無視）
        // $isOtherReq = CwebCaseOtherRequirement::query()
        //     ->where('case_id', $case->id)
        //     ->whereRaw($this->pgNormExpr('responder_employee_number') . " = ?", [$me])
        //     ->exists();
        // if ($isOtherReq) return true;

        return false;
    }
}
