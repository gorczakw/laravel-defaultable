<?php

declare(strict_types=1);

namespace App\Traits;

use Gorczakw\LaravelDefaultable\DefaultableSaveException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait Defaultable
{
    protected string $defaultableColumn = 'is_default';

    public function setAsDefault($withSave = true): void
    {
        $this->setAttribute($this->defaultableColumn, 1);

        if ($withSave) {
            $this->save();
        }
    }

    protected static function bootDefaultable(): void
    {
        static::saved(function (Model|self $model) {
            if ($model->wasChanged($model->defaultableColumn) || $model->getOriginal($model->defaultableColumn) === null) {
                $attribute = (bool)$model->getAttribute($model->defaultableColumn);

                if ($attribute === true) {
                    DB::table($model->getTable())
                        ->where($model->defaultableColumn, 1)
                        ->where($model->getKeyName(), '!=', $model->getKey())
                        ->update([$model->defaultableColumn => 0]);
                }
            }
        });

        static::saving(function (Model|self $model) {
            if ($model->isDirty($model->defaultableColumn)) {
                $attribute = (bool)$model->getAttribute($model->defaultableColumn);

                if ($attribute === false) {
                    $object = DB::table($model->getTable())
                        ->where($model->defaultableColumn, 1)
                        ->where($model->getKeyName(), '!=', $model->getKey())
                        ->first();

                    if (!$object) {
                        throw new DefaultableSaveException();
                    }
                }
            }

            return true;
        });
    }
}
