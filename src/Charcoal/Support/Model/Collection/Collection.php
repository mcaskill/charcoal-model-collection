<?php

namespace Charcoal\Support\Model\Collection;

use InvalidArgumentException;

// From 'illuminate/support'
use Illuminate\Support\Collection as LaravelCollection;

// From 'charcoal-core'
use Charcoal\Model\Collection as CharcoalCollection;
use Charcoal\Model\ModelInterface;

/**
 * Super Model Collection
 *
 * Provides methods to manipulate the collection or retrieve specific models.
 *
 * Note: Some methods were adapted from
 * {@link https://github.com/laravel/framework/blob/5.3/LICENSE.md Laravel/Framework}.
 */
class Collection extends CharcoalCollection
{
    /**
     * Remove and return the first object from the collection.
     *
     * @return object|null Returns the shifted object, or NULL if the collection is empty.
     */
    public function shift()
    {
        return array_shift($this->objects);
    }

    /**
     * Remove and return the last object from the collection.
     *
     * @return object|null Returns the popped object, or NULL if the collection is empty.
     */
    public function pop()
    {
        return array_pop($this->objects);
    }

    /**
     * Add an object onto the beginning of the collection.
     *
     * @param  object $obj An acceptable object.
     * @throws InvalidArgumentException If the given value is not acceptable.
     * @return self
     */
    public function prepend($obj)
    {
        if (!$this->isAcceptable($obj)) {
            throw new InvalidArgumentException(sprintf(
                'Must be a model, received %s',
                (is_object($obj) ? get_class($obj) : gettype($obj))
            ));
        }

        $key = $this->modelKey($obj);
        $this->objects = ([ $key => $obj ] + $this->objects);

        return $this;
    }

    /**
     * Reverse the order of objects in the collection.
     *
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->objects, true));
    }

    /**
     * Filter the collection of objects using the given callback.
     *
     * Iterates over each object in the collection passing them to the $callback function.
     * If the $callback function returns TRUE, the current object is returned into the
     * result collection.
     *
     * @param  callable $callback The callback routine to use.
     * @return static
     */
    public function filter(callable $callback)
    {
        return new static(array_filter($this->objects, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Filter the collection of objects by the given key/value pair.
     *
     * @param  string $key      The property to filter by.
     * @param  mixed  $operator The comparison operator.
     * @param  mixed  $value    The value to filter by.
     * @return static
     */
    public function where($key, $operator, $value = null)
    {
        if (func_num_args() === 2) {
            $value    = $operator;
            $operator = '=';
        }

        return $this->filter($this->operatorForWhere($key, $operator, $value));
    }

    /**
     * Get an operator checker callback.
     *
     * @param  string $key      The property to filter by.
     * @param  mixed  $operator The comparison operator.
     * @param  mixed  $value    The value to filter by.
     * @return \Closure
     */
    protected function operatorForWhere($key, $operator, $value)
    {
        return function ($obj) use ($key, $operator, $value) {
            $retrieved = $obj[$key];

            switch ($operator) {
                default:
                case '=':
                case '==':
                    return $retrieved == $value;
                case '!=':
                case '<>':
                    return $retrieved != $value;
                case '<':
                    return $retrieved < $value;
                case '>':
                    return $retrieved > $value;
                case '<=':
                    return $retrieved <= $value;
                case '>=':
                    return $retrieved >= $value;
                case '===':
                    return $retrieved === $value;
                case '!==':
                    return $retrieved !== $value;
            }
        };
    }

    /**
     * Filter the collection of objects by the given key/value pair.
     *
     * @param  string  $key    The property to filter by.
     * @param  mixed   $values The values to filter by.
     * @param  boolean $strict Whether to use strict comparisons (TRUE)
     *   or "loose" comparisons (FALSE).
     * @return static
     */
    public function whereIn($key, $values, $strict = false)
    {
        $values = $this->asArray($values);

        return $this->filter(function ($obj) use ($key, $values, $strict) {
            return in_array($obj[$key], $values, $strict);
        });
    }

    /**
     * Extract the objects with the specified keys.
     *
     * @param  mixed $keys One or more object primary keys.
     * @return static
     */
    public function only($keys)
    {
        if ($keys === null) {
            return new static($this->objects);
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        return new static(array_intersect_key($this->objects, array_flip($keys)));
    }

    /**
     * Extract a slice of the collection.
     *
     * @param  integer $offset See {@see array_slice()} for a description of $offset.
     * @param  integer $length See {@see array_slice()} for a description of $length.
     * @return static
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->objects, $offset, $length, true));
    }

    /**
     * Extract a portion of the first or last objects from the collection.
     *
     * @param  integer $limit The number of objects to extract.
     * @return static
     */
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param  integer $page    The page offset.
     * @param  integer $perPage The number of objects per page.
     * @return static
     */
    public function forPage($page, $perPage)
    {
        return $this->slice((($page - 1) * $perPage), $perPage);
    }

    /**
     * Sort the collection by the given callback or object property.
     *
     * If a {@see \Closure} is passed, it accepts two parameters.
     * The collection's object first, and its primary key second.
     *
     * ```
     * mixed callback ( ModelInterface $obj, integer|string $key )
     * ```
     *
     * @param  callable|string $sortBy     Sort by a property or a callback.
     * @param  integer         $options    See {@see sort()} for a description of $sort_flags.
     * @param  boolean         $descending If TRUE, the collection is sorted in reverse order.
     * @throws InvalidArgumentException If the comparator is not a string or callback.
     * @return self
     */
    public function sortBy($sortBy, $options = SORT_REGULAR, $descending = false)
    {
        $results = [];

        if (is_string($sortBy)) {
            $callback = function ($obj) use ($sortBy) {
                return $obj[$sortBy];
            };
        } elseif (is_callable($sortBy)) {
            $callback = $sortBy;
        } else {
            throw new InvalidArgumentException(sprintf(
                'The comparator must be a property key or a function, received %s',
                (is_object($sortBy) ? get_class($sortBy) : gettype($sortBy))
            ));
        }

        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($this->objects as $key => $obj) {
            $results[$key] = $callback($obj, $key);
        }

        if ($descending) {
            arsort($results, $options);
        } else {
            asort($results, $options);
        }

        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->objects[$key];
        }

        $this->objects = $results;

        return $this;
    }

    /**
     * Sort the collection in descending order using the given callback or object property.
     *
     * @param  callable|string $sortBy  Sort by a property or a callback.
     * @param  integer         $options See {@see sort()} for a description of $sort_flags.
     * @return self
     */
    public function sortByDesc($sortBy, $options = SORT_REGULAR)
    {
        return $this->sortBy($sortBy, $options, true);
    }

    /**
     * Retrieve one or more random objects from the collection.
     *
     * @param  integer $amount Specifies how many objects should be picked.
     * @throws InvalidArgumentException If the requested amount exceeds the available objects in the collection.
     * @return static
     */
    public function random($amount = 1)
    {
        $count = $this->count();
        if ($amount > $count) {
            throw new InvalidArgumentException(sprintf(
                'You requested %d objects, but there are only %d objects in the collection',
                $amount,
                $count
            ));
        }

        $keys = array_rand($this->objects, $amount);

        if ($amount === 1) {
            return $this->objects[$keys];
        }

        return new static(array_intersect_key($this->objects, array_flip($keys)));
    }

    /**
     * Parse the given value into an array.
     *
     * @link http://php.net/types.array#language.types.array.casting
     *     If an object is converted to an array, the result is an array whose
     *     elements are the object's properties.
     * @param  mixed $value The value being converted.
     * @return array
     */
    protected function asArray($value)
    {
        if (class_exists('\Illuminate\Support\Collection') && $value instanceof LaravelCollection) {
            return $value->all();
        }

        return parent::asArray($value);
    }
}
