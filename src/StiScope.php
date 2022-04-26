<?php

namespace Hpkns\Laravel\Sti;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StiScope implements Scope
{
    /**
     * @inheritdoc
     */
    public function apply(Builder $builder, Model $model)
    {
        /** @var \App\Models\Concerns\SingleTableInheritance $model */
        $name = $model->getStiTypeName();

        if ($name !== null) {
            $builder->where($model->getStiTypeAttributeName(), '=', $name);
        }
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder)
    {
        $builder->macro('withoutSti', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}