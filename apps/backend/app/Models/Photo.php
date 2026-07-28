<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $fillable = ['guest_id', 'image', 'thumbnail', 'audio', 'caption'];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }
}
