<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scrape extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'episode_notes',
        'image_url',
        'audio_url'
    ];
}
