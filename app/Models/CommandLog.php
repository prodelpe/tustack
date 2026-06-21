<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['command', 'status', 'stats', 'error_message', 'started_at', 'finished_at'];

    protected $casts = [
        'stats'       => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function getDurationAttribute(): ?string
    {
        if (! $this->finished_at) {
            return null;
        }

        $seconds = $this->started_at->diffInSeconds($this->finished_at);

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remaining = $seconds % 60;

        return "{$minutes}m {$remaining}s";
    }
}
