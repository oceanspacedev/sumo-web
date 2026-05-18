<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insurance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'insurances';

    protected $guarded = ['id'];

    protected $fillable = [
        'policy_number', 
        'insured_address', 
        'insured_name', 
        'warehouse_code',
        'insured_detail',
        'risk_address',
        'stock_inprov_id',
        'building_inprov_id',
        'stock_worth',
        'building_worth',
        'actual_stock_worth',
        'stock_premium',
        'building_premium',
        'insurance_category_id',
        'extension_of_policy',
        'join_date',
        'expired_date',
        'insurance_scope_id',
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

    public function insurance_scope()
    {
        return $this->belongsTo(InsuranceScope::class);
    }

    public function insurance_category()
    {
        return $this->belongsTo(InsuranceCategory::class);
    }

    public function insurance_update()
    {
        return $this->hasMany(InsuranceUpdate::class);
    }
}
