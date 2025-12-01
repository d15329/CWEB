<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CwebCaseSharedUser extends Model
{
    use HasFactory;

    protected $table = 'cweb_case_shared_users';

    protected $fillable = [
        'case_id',
        'user_id',
        'role',      // shared / other_request / product_owner など
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

