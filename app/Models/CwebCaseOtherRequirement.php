<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CwebCaseOtherRequirement extends Model
{
    use HasFactory;

    protected $table = 'cweb_case_other_requirements';

    protected $fillable = [
        'case_id',
        'content',                    // 要求内容
        'responsible_employee_number' // 対応者社員番号
    ];

    public function case()
    {
        return $this->belongsTo(CwebCase::class, 'case_id');
    }
}
