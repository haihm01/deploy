<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryImportHistory extends Model
{
    use HasFactory;

    protected $table = 'inventory_import_history';

    protected $fillable = [
        'variant_id',
        'supplier_id',
        'quantity',
        'import_price',
        'batch_number',
        'status'
    ];
    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }

    // Quan hệ với bảng `supplier`
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
