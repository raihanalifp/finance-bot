<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AuthorizesOwnedResources
{
    private function authorizeOwner(Model $model): void
    {
        abort_unless((int) $model->getAttribute('user_id') === (int) auth()->id(), 403);
    }
}
