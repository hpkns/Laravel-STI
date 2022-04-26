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
        self::observe(StiObserver::class);
        self::addGlobalScope(new StiScope);
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

        return "{$table}_{$this->getKeyName}";
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
     * @inheritdoc
     */
    public function newFromBuilder($attributes = [], $connection = null): self
    {
        $type = $this->stiTypeBindings[$attributes->{$this->stiAttributeName} ?? null] ?? $this->stiRootModel;

        if (get_class($this) !== $this->stiRootModel && get_class($this) !== $type && !is_subclass_of($this, $type)) {
            throw new RuntimeException('TBD');
        }

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
        $model = get_class($this);

        // If the model in a child of the STI root class, we use it's class name to determine which class the new
        // instance should have. We don't case that the attribute is not set, because the "creating" observer will catch
        // that it's missing and fill it based on the current class.
        // If the current class IS the root one, we check if we can find which class the code
        // intends the model to be based on the STI name attribute.

        if (get_class($this) === $this->stiRootModel) {
            $model = $this->stiTypeBindings[$attributes[$this->stiAttributeName] ?? null] ?? $model;
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
     * Get the string version of the STI type for the current model.
     */
    public function getStiTypeName(): ?string
    {
        return $this->getStiTypeForModel(get_class($this));
    }

    /**
     * Return the type corresponding to a given model.
     */
    public function getStiTypeForModel(?string $model): ?string
    {
        if ($model === null) {
            return null;
        }

        return array_flip($this->stiTypeBindings)[$model] ?? null;
    }

    /**
     * Set the field that commands the STI mapping
     */
    public function setStiFieldAttribute(string $type)
    {
        $this->{$this->stiAttributeName} = $type;
    }

    /**
     * Return the name of the column that hold the object's type.
     */
    public function getStiTypeAttributeName(): string
    {
        return $this->qualifyColumn($this->stiAttributeName);
    }
}
