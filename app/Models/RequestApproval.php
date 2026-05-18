<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestApproval extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'request_approvals';

    protected $guarded = ['id'];

    protected $fillable = ['request_id', 'approval_type', 'approved_by', 'approved_at'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function request_product()
    {
        return $this->belongsTo(RequestBarang::class, 'request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }
}
