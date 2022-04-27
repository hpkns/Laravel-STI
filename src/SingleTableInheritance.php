<?php

namespace Hpkns\Laravel\Sti;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

/**
 * @property \UnitEnum stiTypeBindings  A list of models and their respective type names
 * @property string    stiAttributeName The name of the column that stores the STI mapping binding (default: type)
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

    /**
     * Set the type attribute on model creation.
     */
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

    /**
     * Return the STI type for a given model (or the current object if none is provided)
     */
    public function getStiTypeForModel(?string $model = null): ?string
    {
        $model ??= get_class($this);

        return array_flip($this->stiTypeBindings)[$model] ?? null;
    }

    /**
     * Return the correct STI model based on the values stored in an attribute array.
     */
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
     * After update or create, if the model should be of a subtype, we re-pull it from the database.
     *
     * Caution! By overloading this function, we changed slightly what attributes the model will contain. Normally,
     * updateOrCreate returns the model with only the attributes set by the code in its $attributes array (because it
     * only pushes the record to the database). Now, we re-create the model with the right type by pulling it from the
     * database with a call to $this->fresh(). This causes the model's $attributes array to contain all the columns
     * from the database as opposed to only the fields passed to the updateOrCreate method.
     */
    protected function decoratedUpdateOrCreate(...$args): static
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
        return (new static())->decoratedUpdateOrCreate(...$args);
    }
}
