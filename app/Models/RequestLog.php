<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'request_logs';

    protected $guarded = ['id'];

    protected $fillable = ['user_id', 'request_id', 'activity'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
