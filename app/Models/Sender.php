<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Sender extends Authenticatable
{
    use Notifiable;

    protected $table = 'senders';

    protected $fillable = [
        'email',
        'google_id',
        'access_granted_at',
        'profile_picture',
        'refresh_token'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'access_granted_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'access_granted_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'refresh_token',
        'google_id',
        'remember_token',
    ];

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'sender_id');
    }
}
