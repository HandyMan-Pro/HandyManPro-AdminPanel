<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;
    protected $table = "countries";
    protected $primaryKey = "id";
    protected $fillable = ['name', 'code', 'dial_code', 'currency_name', 'symbol', 'currency_code'];

    protected $casts = [
        'dial_code' => 'integer',
    ];
    
    public function states()
    {
        return $this->hasMany(State::class, 'country_id','id');
    }
}
