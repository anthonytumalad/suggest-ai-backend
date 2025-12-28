<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Sender extends Authenticatable
{
    use Notifiable;

    protected $table = 'senders';

    // Only the fields we actually need for login
    protected $fillable = [
        'name',
        'email',
        'google_id',
    ];

    protected $hidden = [
        'google_id',
        'remember_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'sender_id');
    }
}
