<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $fillable = ['item_code', 'product', 'category_id', 'unit_type_id', 'price', 'description', 'product_image', 'stock'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit_type()
    {
        return $this->belongsTo(UnitType::class);
    }

    public function getProductImage()
    {
        if(!$this->product_image){
            return asset('images/no_image.jpeg');
        }

        return asset('storage/product/'.$this->product_image);
    }
}
