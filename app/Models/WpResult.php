<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WpResult extends Model
{
    /** @use HasFactory<\Database\Factories\WpResultFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'alternative_id',
        's_vector',
        'v_vector',
        'individual_rank',
    ];

    protected function casts(): array
    {
        return [
            's_vector' => 'decimal:10',
            'v_vector' => 'decimal:10',
            'individual_rank' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alternative(): BelongsTo
    {
        return $this->belongsTo(Alternative::class);
    }
}
