<?php

namespace App\Models;

use Database\Factories\RunPointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunPoint extends Model
{
    /** @use HasFactory<RunPointFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'lat',
        'lng',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Run, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }
}
