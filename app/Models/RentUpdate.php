<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentUpdate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rent_updates';

    protected $guarded = ['id'];

    protected $fillable = [
        'rent_id',
        'rent_code',
        'first_party',
        'second_party',
        'rent_per_year',
        'cvcs_fund',
        'online_fund',
        'join_date',
        'expired_date',
        'deduction_evidence',
        'deduction_evidence_file',
        'document',
        'document_file',
        'payment_evidence_file',
        'status',
        'month_before_reminder',
        'user_id',
        'notes',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function rent()
    {
        return $this->belongsTo(Rent::class, 'rent_id');
    }
}
