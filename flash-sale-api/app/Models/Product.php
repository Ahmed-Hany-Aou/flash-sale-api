<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'total_stock', 'price'];

    public function holds()
    {
        return $this->hasMany(Hold::class);
    }
}
