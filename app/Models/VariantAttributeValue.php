<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantAttributeValue extends Model
{
    use HasFactory;

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    /**
     * Mối quan hệ với model AttributeValue
     * Một VariantAttributeValue thuộc về một AttributeValue
     */
    public function attributeValue()
    {
        return $this->belongsTo(AttributeValue::class);
    }

}
