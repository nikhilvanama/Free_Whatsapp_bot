<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class whatsaap_stage_check extends Model
{
    use HasFactory;
    public $table = 'whatsapp_stage_checks';
    protected $fillable = ['stage', 'phone'];
}
