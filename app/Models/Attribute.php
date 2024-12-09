<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use HasFactory;
    protected $table = 'attributes';

    protected $fillable = [
        'name',
        'slug',
        'type',
    ];

    public function attributeValues() {
        return $this->hasMany(AttributeValue::class);
    }
    public function values()
{
    return $this->hasMany(AttributeValue::class);
}
}

