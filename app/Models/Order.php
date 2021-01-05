<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Product;
use App\Models\User;

class Order extends Model
{
    // use HasFactory;
    protected $table = 'order';
    
    protected $fillable = [
        'user',
        'product',
        'amount',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'id', 'user');
    }

    public function product() {
        return $this->belongsTo(Product::class, 'id', 'product');
    }
}
