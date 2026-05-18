<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'insurance_providers';

    protected $guarded = ['id'];

    protected $fillable = ['insurance_provider'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function insurance()
    {
        return $this->hasMany(Insurance::class);
    }
}
