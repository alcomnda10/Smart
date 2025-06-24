<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Water extends Model
{

    protected $fillable = ['sensor_id', 'type', 'status',  'guidance', 'icon'];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }
}
