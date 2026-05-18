<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $fillable = ['area'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function division()
    {
        return $this->hasMany(Divisi::class);
    }
}
