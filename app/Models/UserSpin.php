<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSpin extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'remaining_spins', 'last_spin_date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
