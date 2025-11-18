<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BordaResult extends Model
{
    /** @use HasFactory<\Database\Factories\BordaResultFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'alternative_id',
        'total_borda_points',
        'final_rank',
    ];

    protected function casts(): array
    {
        return [
            'total_borda_points' => 'integer',
            'final_rank' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function alternative(): BelongsTo
    {
        return $this->belongsTo(Alternative::class);
    }
}
