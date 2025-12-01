<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CwebCaseWillAllocation extends Model
{
    use HasFactory;

    protected $table = 'cweb_case_will_allocations';

    protected $fillable = [
        'case_id',
        'employee_number',
        'employee_name',
        'percentage',  // 0〜100
    ];

    protected $casts = [
        'percentage' => 'integer',
    ];

    public function case()
    {
        return $this->belongsTo(CwebCase::class, 'case_id');
    }
}
