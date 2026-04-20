<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'topic',
        'tanggal',
        'jam_mulai',
        'duration',
        'nama_pemesan',
        'unit',
        'no_hp',
        'join_url',
        'password',
        'status',
        'zoom_meeting_id'

    ];
}
