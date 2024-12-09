<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'categories';
    protected $fillable = ['id','name','slug','image','is_active'];

    public function product(){
        return $this->hasMany(Product::class);
    }
    protected $casts = [
        'is_active' => 'boolean',
    ];
}