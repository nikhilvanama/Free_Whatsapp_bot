<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class appointment_table extends Model
{
    use HasFactory;
    protected $table = 'appointment_table';
    protected $fillable = ['phone', 'date', 'time', 'status'];
}
