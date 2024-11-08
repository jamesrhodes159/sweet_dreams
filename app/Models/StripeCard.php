<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeCard extends Model
{
    use HasFactory;

    protected $table = 'stripe_cards';
    protected $fillable = [
        'user_id', 'brand', 'exp_month', 'exp_year', 'last4', 'fingerprint', 'token', 'is_default'
    ];
}
