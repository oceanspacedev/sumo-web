<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestSetting extends Model
{
    use HasFactory;

    protected $table = 'request_settings';

    protected $guarded = ['id'];

    protected $fillable = ['request_month', 'open_date', 'closed_date', 'request_detail'];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
