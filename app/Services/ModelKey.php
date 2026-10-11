<?php

namespace App\Services;

class ModelKey
{
    public static function get(mixed $model): ?string
    {
        if (! $model) {
            return null;
        }

        if (isset($model->_id)) {
            return (string) $model->_id;
        }

        return isset($model->id) ? (string) $model->id : null;
    }
}
