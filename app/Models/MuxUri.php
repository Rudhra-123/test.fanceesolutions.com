<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MuxVideo extends Model
{
    use HasFactory;

    protected $table = 'mux_videos';

    protected $fillable = ['uri', 'order_id'];
}
