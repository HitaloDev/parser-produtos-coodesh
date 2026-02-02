<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'code',
        'status',
        'imported_t',
        'url',
        'creator',
        'created_t',
        'last_modified_t',
        'product_name',
        'quantity',
        'brands',
        'categories',
        'labels',
        'cities',
        'purchase_places',
        'stores',
        'ingredients_text',
        'traces',
        'serving_size',
        'serving_quantity',
        'nutriscore_score',
        'nutriscore_grade',
        'main_category',
        'image_url',
    ];

    protected $casts = [
        'status' => ProductStatus::class,
        'imported_t' => 'datetime',
        'created_t' => 'integer',
        'last_modified_t' => 'integer',
        'serving_quantity' => 'float',
        'nutriscore_score' => 'integer',
    ];

    public $timestamps = true;
}
