<?php

namespace Hpkns\Laravel\Sti;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

/**
 * @property \UnitEnum stiTypeBindings  A list of models and their respective type names
 * @property string    stiAttributeName The name of the column that stores the STI mapping binding
 *
 * @method static Builder|QueryBuilder withoutSti()
 */
trait SingleTableInheritance
{
    /**
     * Boot the trait when a new instance of the model is created.
     */
    public static function bootSingleTableInheritance(): void
    {
        self::addGlobalScope(new StiScope);
    }

    public function initializeSingleTableInheritance()
    {
        $this->{$this->stiTypeKey()} = $this->getStiTypeForModel();
    }

    /**
     * Get the name of the table from the root model.
     */
    public function getTable(): string
    {
        if (static::isStiRoot()) {
            return parent::getTable();
        }

        return static::getStiRootModelInstance()->getTable();
    }

    /**
     * Infer the name of the foreign key from the root model.
     */
    public function getForeignKey(): string
    {
        $table = Str::snake(class_basename(static::stiRootModel()));

        return "{$table}_{$this->getKeyName()}";
    }

    /**
     * Check if the current class is the root of the STI structure.
     */
    protected static function isStiRoot(): bool
    {
        return static::class === self::stiRootModel();
    }

    /**
     * Return the class used as the root of the STI structure.
     */
    protected static function stiRootModel(): string
    {
        return self::class;
    }

    /**
     * Return an instance of the model that is at the root of the STI structure.
     */
    protected function getStiRootModelInstance(): Model
    {
        $class = static::stiRootModel();

        return new $class;
    }

    /**
     * Return the key used...
     */
    public function stiTypeKey($qualified = false): string
    {
        return $this->stiAttributeName ?? 'type';

        return $qualified ? $type : $this->qualifyColumn($type);
    }

    public function stiTypeAttribute(): ?string
    {
        return $this->{$this->stiTypeKey()};
    }

    public function setStiTypeAttribute()
    {
        $this->{$this->stiTypeKey()} = $this->getStiTypeForModel();
    }

    public function getStiTypeForModel(?string $model = null): ?string
    {
        $model ??= get_class($this);

        return array_flip($this->stiTypeBindings)[$model] ?? null;
    }

    protected function getStiModelFromTypeAttributes(array $attributes): ?string
    {
        $type = $attributes[$this->stiTypeKey()] ?? null;

        if (empty($type)) {
            return self::stiRootModel();
        }

        return $this->stiTypeBindings[$type] ?? self::stiRootModel();
    }

    /**
     * @inheritdoc
     */
    public function newFromBuilder($attributes = [], $connection = null): self
    {
        $type = $this->getStiModelFromTypeAttributes((array)$attributes);

        $model = (new $type)->newInstance([], true);

        $model->setRawAttributes((array)$attributes, true);

        $model->setConnection($connection ?: $this->getConnectionName());

        $model->fireModelEvent('retrieved', false);

        return $model;
    }

    /**
     * This method is used by all the Builder's "create" and "***orCreate".
     *
     * @inheritdoc
     */
    public function newInstance($attributes = [], $exists = false): self
    {
        $model = static::class;

        if ($model === static::stiRootModel()) {
            $model = $this->getStiModelFromTypeAttributes($attributes);
        }

        $model = new $model((array)$attributes);

        $model->exists = $exists;

        $model->setConnection(
            $this->getConnectionName()
        );

        $model->setTable($this->getTable());

        $model->mergeCasts($this->casts);

        return $model;
    }

    /**
     * After update or create, if the model should be of a sub-type, we re-pull it from the database.
     */
    protected function decorateUpdateOrCreate(...$args): static
    {
        $model = $this->forwardCallTo($this->newQuery(), 'updateOrCreate', $args);

        if (!static::isStiRoot()) {
            return $model;
        }

        if ($model->wasRecentlyCreated) {
            $model = $model->fresh();
            $model->wasRecentlyCreated = true;
        }

        return $model;
    }

    /**
     * Allow updateOrCreate method to return the right type.
     */
    public static function updateOrCreate(...$args): static
    {
        return (new static())->decorateUpdateOrCreate(...$args);
    }
}
