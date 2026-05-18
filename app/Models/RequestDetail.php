<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'request_details';

    protected $guarded = ['id'];

    protected $fillable = ['request_id', 'product_id', 'qty_request', 'qty_remaining', 'qty_approved', 'description'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function request_product()
    {
        return $this->belongsTo(RequestBarang::class, 'request_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
