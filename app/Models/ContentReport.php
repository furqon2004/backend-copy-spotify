<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'reporter_id',
        'target_type',
        'target_id',
        'reason',
        'status',
        'created_at'
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}