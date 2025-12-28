<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackSummary extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'summary_data' => 'array',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
