<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Scope to select non-draft interviews, articles, reviews
 */
class NonDraftScope implements Scope
{

    public function apply(Builder $builder, Model $model)
    {
        $builder->where('draft', false);
    }
}
