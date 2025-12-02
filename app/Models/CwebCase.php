<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CwebCase extends Model
{
    protected $fillable = [
        'manage_no',
        'status',
        'customer_name',
        'sales_contact_employee_number',
        'cost_responsible_code',
        'category_standard',
        'category_pcn',
        'category_other',
        'product_group',
        'product_code',
        'pcn_note',
        'other_request_note',
        'will_registration_cost',
        'will_registration_comment',
        'will_monthly_cost',
        'will_monthly_comment',
        'related_qweb',
        'folder_path',
        'created_by_user_id',
        'abolished_by_user_id',
        'abolished_comment',
        'abolished_at',
    ];

    protected $casts = [
        'category_standard' => 'boolean',
        'category_pcn' => 'boolean',
        'category_other' => 'boolean',
        'abolished_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function abolishedBy()
    {
        return $this->belongsTo(User::class, 'abolished_by_user_id');
    }

    public function sharedUsers()
    {
        return $this->hasMany(CwebCaseSharedUser::class, 'case_id');
    }

    public function pcnItems()
    {
        return $this->hasMany(CwebCasePcnItem::class, 'case_id');
    }

    public function otherRequirements()
    {
        return $this->hasMany(CwebCaseOtherRequirement::class, 'case_id');
    }

    public function willAllocations()
    {
        return $this->hasMany(CwebCaseWillAllocation::class, 'case_id');
    }

    public function comments()
    {
        return $this->hasMany(CwebCaseComment::class, 'case_id')->latest();
    }

    /**
     * 管理番号の次番号を生成（SP-250001 → SP-250002...）
     * シンプルに「最大値＋1」で実装。
     */
    public static function nextManagementNo(): string
    {
        $prefix = 'SP-25'; // とりあえず固定（将来、年から動的生成でもOK）

        $max = static::where('manage_no', 'like', $prefix.'%')
            ->orderBy('manage_no', 'desc')
            ->value('manage_no');

        if (!$max) {
            return $prefix.'0001';
        }

        // 数字部分を取り出し＋1
        $numberPart = (int)substr($max, strlen($prefix));
        $numberPart++;

        return sprintf('%s%04d', $prefix, $numberPart);

                $lastManageNo = self::orderBy('id', 'desc')->value('manage_no');

        if (!$lastManageNo || !preg_match('/SP-(\d{6})/', $lastManageNo, $m)) {
            // まだ1件もない or フォーマット外 → 初期値
            $nextNumber = 250001;
        } else {
            // 数値部分を取り出して +1
            $nextNumber = (int)$m[1] + 1;
        }

        // ゼロ埋め6桁で SP-xxxxxx を返す
        return 'SP-' . sprintf('%06d', $nextNumber);
    
    }
}

