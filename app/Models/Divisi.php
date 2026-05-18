<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Divisi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'divisions';

    protected $guarded = ['id'];

    protected $fillable = ['division', 'area_id'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function user()
    {
        return $this->hasMany(User::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function request_type()
    {
        return $this->hasMany(RequestType::class);
    }
}
