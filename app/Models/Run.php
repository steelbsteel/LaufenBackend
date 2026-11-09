<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RunFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Run extends Model
{
    /** @use HasFactory<RunFactory> */
    use HasFactory;

    protected $fillable = [
        'started_at',
        'duration_sec',
        'distance_m',
        'calories',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'duration_sec' => 'integer',
            'distance_m' => 'integer',
            'calories' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<RunPoint, $this> */
    public function points(): HasMany
    {
        return $this->hasMany(RunPoint::class)->orderBy('recorded_at');
    }

    /** Темп (сек/км). null, если дистанция нулевая */
    protected function avgPaceSecPerKm(): Attribute
    {
        return Attribute::get(
            fn (): ?int => $this->distance_m > 0
                ? (int) round($this->duration_sec / ($this->distance_m / 1000))
                : null,
        );
    }
}
