<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Form extends Model
{
    use SoftDeletes;

    protected $table = 'forms';

    protected $fillable = [
        'title',
        'description',
        'slug',
        'is_active',
        'is_read',
        'allow_anonymous',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_read' => 'boolean',
        'allow_anonymous' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_read' => false,
        'allow_anonymous' => false,
    ];


    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Form $form) {
            if (!empty($form->slug)) {
                return;
            }

            $baseSlug = Str::slug($form->title) ?: 'form';
            $slug = $baseSlug;
            $counter = 1;

            while (static::withTrashed()->where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $form->slug = $slug;
        });
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'form_id');
    }

    public function scopeWithFeedbackCounts($query)
    {
        return $query->withCount([
            'feedbacks as total_feedbacks',
            'feedbacks as new_feedbacks' => function ($q) {
                $q->where('is_read', false);
            }
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
