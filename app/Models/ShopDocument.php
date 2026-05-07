<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopDocument extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'shop_documents';

    protected $fillable = ['shop_id', 'document_id', 'is_verified'];

    protected $casts = [
        'shop_id'     => 'integer',
        'document_id' => 'integer',
        'is_verified' => 'integer',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id')->withTrashed();
    }

    public function document()
    {
        return $this->belongsTo(Documents::class, 'document_id', 'id')->withTrashed();
    }
}
