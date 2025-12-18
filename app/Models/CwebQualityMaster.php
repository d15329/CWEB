<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CwebQualityMaster extends Model
{
    protected $table = 'cweb_quality_masters';
    protected $fillable = [
  'employee_number',
  'name',
  'email',
  'is_active',
  'product_group',
  'product_code',
];

}
