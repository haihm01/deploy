<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = ['price', 'sale_price', 'end_sale', 'image', 'product_id'];

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'variant_attribute_values');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function carts()
    {
        return $this->hasMany(related: Cart::class);
    }

    public function inventoryStock()
    {
        return $this->hasOne(InventoryStock::class);
    }
    public function inventoryImports()
    {
        return $this->hasMany(InventoryImport::class); // Quan hệ với InventoryImport
    }

    public function inventoryImportHistories()
    {
        return $this->hasMany(InventoryImportHistory::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
