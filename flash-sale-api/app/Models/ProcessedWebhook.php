<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessedWebhook extends Model
{
    use HasFactory;

    protected $fillable = ['idempotency_key', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];
}
