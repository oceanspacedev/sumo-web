<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BadanUsaha extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'badan_usahas';

    protected $guarded = ['id'];

    protected $fillable = ['badan_usaha'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function user()
    {
        return $this->hasMany(User::class);
    }
}
