<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CwebProductOwner extends Model
{
    protected $table = 'cweb_product_owners';
    protected $fillable = [
  'employee_number',
  'name',
  'email',
  'is_active',
  'product_group',
  'product_code',
];

}

