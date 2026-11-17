<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * Sanctum writes "last_used_at" on EVERY request. With a far-away database that is one
 * extra round trip per request, so only save it when it is more than a minute old.
 */
class PersonalAccessToken extends SanctumToken
{
    protected $table = 'personal_access_tokens';

    public function save(array $options = []): bool
    {
        $dirty = array_keys($this->getDirty());
        $onlyLastUsed = $this->exists && $dirty === ['last_used_at'];

        if ($onlyLastUsed) {
            $previous = $this->getOriginal('last_used_at');
            if ($previous && now()->diffInSeconds($previous, true) < 60) {
                $this->syncOriginal(); // skip the database write

                return true;
            }
        }

        return parent::save($options);
    }
}
