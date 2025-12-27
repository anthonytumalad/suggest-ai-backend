<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $hidden = ['sender_id'];

    protected $fillable = [
        'form_id',
        'sender_id',
        'is_anonymous',
        'is_read',
        'role',
        'rating',
        'feedback',
        'suggestions',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'is_read' => 'boolean',
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['sender_visible'];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function sender()
    {
        return $this->belongsTo(Sender::class, 'sender_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function getSenderVisibleAttribute(): bool
    {
        return !$this->is_anonymous;
    }
}
