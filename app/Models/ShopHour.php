<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopHour extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shop_hours';

    protected $fillable = [
        'shop_id',
        'day',
        'start_time',
        'end_time',
        'is_holiday',
        'breaks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_holiday' => 'boolean',
        'breaks' => 'array',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }
}
