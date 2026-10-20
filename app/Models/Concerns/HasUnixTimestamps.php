<?php

namespace App\Models\Concerns;

use DateTimeInterface;

/**
 * Serializes model dates (created_at, updated_at, and any datetime cast) as
 * Unix timestamps (epoch SECONDS, UTC) in JSON instead of ISO strings, so the
 * frontend can format them in the user's own timezone. Also exposes
 * human-readable companion fields (created_at_human / updated_at_human).
 */
trait HasUnixTimestamps
{
    /** Eloquent calls this automatically for every model that uses the trait. */
    public function initializeHasUnixTimestamps(): void
    {
        $this->appends = array_values(array_unique(array_merge(
            $this->appends ?? [],
            ['created_at_human', 'updated_at_human'],
        )));
    }

    protected function serializeDate(DateTimeInterface $date): int
    {
        return $date->getTimestamp();
    }

    public function getCreatedAtHumanAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->format('M d, Y g:i A') : null;
    }

    public function getUpdatedAtHumanAttribute(): ?string
    {
        return $this->updated_at ? $this->updated_at->format('M d, Y g:i A') : null;
    }
}
