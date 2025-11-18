<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alternative extends Model
{
    /** @use HasFactory<\Database\Factories\AlternativeFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'code',
        'name',
        'nip',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
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
}
