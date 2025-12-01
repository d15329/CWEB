<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CwebCaseComment extends Model
{
    use HasFactory;

    protected $table = 'cweb_case_comments';

    protected $fillable = [
        'case_id',
        'user_id',
        'body',
    ];

    public function case()
    {
        return $this->belongsTo(CwebCase::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

