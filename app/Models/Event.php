<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    protected $fillable = [
        'event_name',
        'event_date',
        'borda_settings',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'borda_settings' => 'array',
        ];
    }

    public function alternatives(): HasMany
    {
        return $this->hasMany(Alternative::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(Criterion::class);
    }

    public function decisionMakers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')
            ->withPivot('is_leader', 'assigned_at')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->decisionMakers();
    }

    public function leaders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')
            ->wherePivot('is_leader', true)
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function wpResults(): HasMany
    {
        return $this->hasMany(WpResult::class);
    }

    public function bordaResults(): HasMany
    {
        return $this->hasMany(BordaResult::class);
    }

    public function getLeader(): ?User
    {
        return $this->leaders()->first();
    }

    public function hasLeader(): bool
    {
        return $this->leaders()->exists();
    }
}
