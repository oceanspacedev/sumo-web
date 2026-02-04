<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestBarang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'requests';

    protected $guarded = ['id'];

    protected $fillable = ['request_code', 'user_id', 'date', 'total_cost', 'status_po', 'status_client', 'closed_by', 'closed_at', 'approved_by', 'approved_at', 'request_type_id', 'request_file', 'notes', 'user_notes', 'audit_notes'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function request_type()
    {
        return $this->belongsTo(RequestType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function closedby()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function approvedby()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function request_detail()
    {
        return $this->hasMany(RequestDetail::class, 'request_id');
    }

    public function approval()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function request_approval()
    {
        return $this->hasMany(RequestApproval::class, 'request_id');
    }

    public function product()
    {
        return $this->hasManyThrough(Product::class, RequestDetail::class);
    }
}
