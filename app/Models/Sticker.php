<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sticker extends Model
{
    use HasFactory;

    protected $table = "stickers";

    protected $fillable = [
        'sticker_name',
        'sticker_image',
        'price',
        'sticker_type',
        'is_active'
    ];
}
