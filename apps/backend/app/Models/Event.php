<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['title', 'groom_name', 'bride_name', 'wedding_date', 'frame_image', 'slug'];

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }
}
