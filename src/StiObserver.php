<?php

namespace Hpkns\Laravel\Sti;

use Illuminate\Database\Eloquent\Model;

class StiObserver
{
    public function creating(Model $model)
    {
        if ($name = $model->getStiTypeName()) {
            $model->setStiFieldAttribute($name);
        }
    }
}