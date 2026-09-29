<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One viewer's vote in a vote session.
 */
#[Fillable(['voter_id', 'choice'])]
class Vote extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['choice' => 'integer'];
    }
}
