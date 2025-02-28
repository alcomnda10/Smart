<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Flame extends Model
{
    protected $fillable = ['sensor_id', 'type', 'status',  'guidance'];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return Carbon::parse($date)->format('Y-m-d H:i:s'); 
    }
}
