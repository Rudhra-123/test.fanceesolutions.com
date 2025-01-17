<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MuxVideo extends Model
{
    use HasFactory;

    protected $fillable = ['asset_id', 'playback_id', 'order_id'];
}
