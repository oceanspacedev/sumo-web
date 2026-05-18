<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceUpdate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'insurance_updates';

    protected $guarded = ['id'];

    protected $fillable = [
        'insurance_id',
        'policy_number', 
        'insured_address', 
        'stock_inprov_id',
        'building_inprov_id',
        'stock_worth',
        'actual_stock_worth',
        'stock_premium',
        'building_worth',
        'building_premium',
        'extension_of_policy',
        'join_date',
        'expired_date',
        'user_id',
        'payment_evidence',
        'status',
        'notes',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function stock_insurance_provider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'stock_inprov_id');
    }

    public function building_insurance_provider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'building_inprov_id');
    }

    public function insurance()
    {
        return $this->belongsTo(Insurance::class, 'insurance_id');
    }
}
