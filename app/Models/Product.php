<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'images', 'description', 'category_id',
        'is_featured', 'is_active',
    ];

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // một sản phẩm có nhiều đánh giá
    public function rates()
    {
        return $this->hasMany(Rate::class);
    }
    protected $casts = [
        'is_featured' => 'boolean',
        'is_good_deal' => 'boolean',
        'is_active' => 'boolean',
    ];
}