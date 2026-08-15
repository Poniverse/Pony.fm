<?php

namespace Poniverse\Lib\Entity;

/**
 * Poniverse\Lib\Entity\Entity.
 *
 * @property-read string $id
 */
abstract class Entity
{
    /**
     * Entity ID.
     *
     * @var int|string
     */
    protected $id;

    /**
     * This will contain the list of attributes that have been modified
     * since the entity was last synced.
     *
     * @var array
     */
    protected $dirty = [];

    /**
     * Option to toggle tracking changes.
     *
     * @var bool
     */
    protected $checkDirty = false;

    /**
     * Stores the model data.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Defined read-only Attributes.
     *
     * @var array
     */
    protected $readOnlyProperties = [];

    public function __construct(array $attributes = [])
    {
        $this->hydrate($attributes);
        $this->checkDirty = true;
    }

    /**
     * Hydrate the entity with data.
     *
     * @param array $data
     *
     * @return array
     */
    abstract protected function hydrate(array $data);

    /**
     * Determine if any attributes have been modified.
     *
     * @return bool
     */
    public function isDirty()
    {
        return count($this->dirty) > 0;
    }

    /**
     * Returns all entity attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Returns all changed attributes.
     *
     * @return array
     */
    public function getDirtyAttributes()
    {
        $attributes = [];

        foreach ($this->dirty as $name) {
            $attributes[$name] = $this->$name;
        }

        return $attributes;
    }

    public function __get($name)
    {
        if (! isset($this->attributes[$name]) && $name !== 'id') {
            throw new \InvalidArgumentException('Property doesn\'t exist!');
        }

        return $name === 'id' ? $this->id : $this->attributes[$name];
    }

    /**
     * Track modifications made to attributes.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        if ($this->checkDirty && ($name === 'id' || in_array($name, $this->readOnlyProperties, true))) {
            throw new \InvalidArgumentException("The '{$name}' property is read-only");
        }

        if ($this->checkDirty && ! in_array($name, $this->dirty, true)) {
            $this->dirty[] = $name;
        }

        $this->attributes[$name] = $value;
    }
}
