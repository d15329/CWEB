<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CwebCasePcnItem extends Model
{
    use HasFactory;

    protected $table = 'cweb_case_pcn_items';

    protected $fillable = [
        'case_id',
        'category',      // spec / man / machine / material / method / measurement / environment / other
        'title',         // ラベル変更など
        'months_before', // 何か月前連絡
        'note',
    ];

    protected $casts = [
        'months_before' => 'integer',
    ];

    public function case()
    {
        return $this->belongsTo(CwebCase::class, 'case_id');
    }
}

