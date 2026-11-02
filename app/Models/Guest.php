<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    protected $fillable = ['first_name', 'last_name', 'email', 'phone'];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
