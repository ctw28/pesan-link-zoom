<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoomAccount extends Model
{
    protected $fillable = [
        'name',
        'email',
        'capacity',
        'client_id',
        'client_secret',
        'is_active'
    ];

    // relasi ke meeting
    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }
}
