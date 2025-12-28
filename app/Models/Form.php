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
        'submission_preference_enabled',
        'role_selection_enabled',
        'rating_enabled',
        'suggestions_enabled',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_read' => 'boolean',
        'submission_preference_enabled' => 'boolean',
        'role_selection_enabled' => 'boolean',
        'rating_enabled' => 'boolean',
        'suggestions_enabled' => 'boolean',
    ];

    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_read' => false,
        'submission_preference_enabled' => true,
        'role_selection_enabled' => true,
        'rating_enabled' => true,
        'suggestions_enabled' => true,
    ];


    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Form $form) {
            if (empty($form->slug)) {
                $form->slug = $form->generateUniqueSlug($form->title);
            }
        });

        static::updating(function (Form $form) {
            if ($form->isDirty('title')) {
                $form->slug = $form->generateUniqueSlug($form->title, $form->id);
            }
        });
    }


    private function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($title) ?: 'form';
        $slug = $baseSlug;
        $counter = 1;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
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
