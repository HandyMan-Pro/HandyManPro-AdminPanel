<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Facades\Auth;


class PostJobRequest extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $table = 'post_job_requests';
    protected $fillable = [
        'title', 'customer_id', 'status' ,'description','provider_id','reason','price','date','job_price'
    ];

    protected $casts = [
        'customer_id'  => 'integer',
        'provider_id'  => 'integer',
        'price' => 'double',
        'job_price' => 'double',
    ];

    protected $with = ['postServiceMapping.service']; // Always load the relationships

    public function postServiceMapping(){
        return $this->hasMany(PostJobServiceMapping::class, 'post_request_id','id');
    }
    public function scopeMyPostJob($query)
    {
        if(auth()->user()->hasRole('admin')) {
            return $query;
        }

        if(auth()->user()->hasRole('user')) {
            return $query->where('customer_id', Auth::id());
        }
        if(auth()->user()->hasRole('provider')) {
            return $query;
        }
        return $query;
    }
    public function postBidList(){
        return $this->hasMany(PostJobBid::class, 'post_request_id','id');
    }
    public function provider(){
        return $this->belongsTo(User::class,'provider_id', 'id')->withTrashed();
    }
    public function customer(){
        return $this->belongsTo(User::class,'customer_id', 'id')->withTrashed();
    }
     public function registerMediaCollections(): void
    {
        $this->addMediaCollection('service_attachment')->singleFile(); // This sets up the collection
    }
}
