<?php

namespace Charcoal\Support\Model\Repository;

use PDO;
use Closure;
use RuntimeException;
use InvalidArgumentException;

// From 'charcoal-core'
use Charcoal\Source\Filter;
use Charcoal\Source\FilterInterface;
use Charcoal\Source\Order;
use Charcoal\Source\OrderInterface;

/**
 * Scoped Object Collection Loader
 *
 * Provides support for default filters, orders, and pagination,
 * that are automatically applied after resetting the loader.
 */
class ScopedCollectionLoader extends ModelCollectionLoader
{
    /**
     * Store pre-defined raw query filters.
     *
     * @var array
     */
    protected $defaultFilters = [];

    /**
     * Store pre-defined raw query sorting.
     *
     * @var array
     */
    protected $defaultOrders = [];

    /**
     * Store pre-defined raw query pagination.
     *
     * @var mixed
     */
    protected $defaultPagination;

    /**
     * Return a new CollectionLoader object.
     *
     * @param array $data The loader's dependencies.
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        if (isset($data['default_filters'])) {
            $this->setDefaultFilters($data['default_filters']);
        }

        if (isset($data['default_orders'])) {
            $this->setDefaultOrders($data['default_orders']);
        }

        if (isset($data['default_pagination'])) {
            $this->setDefaultPagination($data['default_pagination']);
        }

        $this->applyDefaults();
    }

    /**
     * Clone the collection loader.
     *
     * @param  mixed $data An array of customizations for the clone or an object model.
     */
    #[\Override]
    public function cloneWith($data): static
    {
        if (!is_array($data)) {
            $data = [
                'model' => $data,
            ];
        }

        $data['default_filters']    = $this->defaultFilters;
        $data['default_orders']     = $this->defaultOrders;
        $data['default_pagination'] = $this->defaultPagination;

        return parent::cloneWith($data);
    }

    /**
     * Reset everything but the model and apply the default filters, orders, and pagination.
     */
    #[\Override]
    public function reset(): static
    {
        parent::reset();
        $this->applyDefaults();

        return $this;
    }

    /**
     * Reset everything but the model and call the given function.
     *
     * @param  callable|null $callback A callback bound to the collection loader.
     */
    public function withoutDefaults(?callable $callback = null): static
    {
        parent::reset(true);

        if ($callback !== null) {
            $callback = Closure::bind($callback, $this, static::class);
            $callback();
        }

        return $this;
    }

    /**
     * Apply the default filters, orders, and pagination.
     *
     * @return void
     */
    protected function applyDefaults()
    {
        $this->applyDefaultFilters();
        $this->applyDefaultOrders();
        $this->applyDefaultPagination();
    }

    /**
     * Apply the default filters.
     *
     * @return void
     */
    protected function applyDefaultFilters()
    {
        if ($this->hasDefaultFilters()) {
            $this->source()->setFilters($this->defaultFilters);
        }
    }

    /**
     * Replace the default filter(s) on this object.
     *
     * Note: Any existing filters are dropped.
     *
     * @param  mixed[] $filters One or more filters to set on this expression.
     */
    protected function setDefaultFilters(array $filters): static
    {
        $this->defaultFilters = [];
        $this->addDefaultFilters($filters);
        return $this;
    }

    /**
     * Append one or more default filters on this object.
     *
     * @param  mixed[] $filters One or more filters to add on this expression.
     */
    public function addDefaultFilters(array $filters): static
    {
        foreach ($filters as $filter) {
            $this->addDefaultFilter($filter);
        }

        return $this;
    }

    /**
     * Append a default filter on this object.
     *
     * @param  mixed $filter The expression string, structure, object, or callable to be parsed.
     */
    public function addDefaultFilter($filter): static
    {
        $this->defaultFilters[] = $filter;
        return $this;
    }

    /**
     * Determine if the object has any default filters.
     */
    public function hasDefaultFilters(): bool
    {
        return !empty($this->defaultFilters);
    }

    /**
     * Apply the default orders.
     *
     * @return void
     */
    protected function applyDefaultOrders()
    {
        if ($this->hasDefaultOrders()) {
            $this->source()->setOrders($this->defaultOrders);
        }
    }

    /**
     * Replace the default order(s) on this object.
     *
     * Note: Any existing orders are dropped.
     *
     * @param  mixed[] $orders One or more orders to set on this expression.
     */
    protected function setDefaultOrders(array $orders): static
    {
        $this->defaultOrders = [];
        $this->addDefaultOrders($orders);
        return $this;
    }

    /**
     * Append one or more default orders on this object.
     *
     * @param  mixed[] $orders One or more orders to add on this expression.
     */
    public function addDefaultOrders(array $orders): static
    {
        foreach ($orders as $order) {
            $this->addDefaultOrder($order);
        }

        return $this;
    }

    /**
     * Append a default order on this object.
     *
     * @param  mixed $order The expression string, structure, object, or callable to be parsed.
     */
    public function addDefaultOrder($order): static
    {
        $this->defaultOrders[] = $order;
        return $this;
    }

    /**
     * Determine if the object has any default orders.
     */
    public function hasDefaultOrders(): bool
    {
        return !empty($this->defaultOrders);
    }

    /**
     * Apply the default pagination.
     *
     * @return void
     */
    protected function applyDefaultPagination()
    {
        if ($this->hasDefaultPagination()) {
            $this->source()->setPagination($this->defaultPagination);
        }
    }

    /**
     * Replace the default pagination on this object.
     *
     * Note: Any existing pagination is dropped.
     *
     * @param  mixed $pagination A page number / number per page value.
     */
    protected function setDefaultPagination($pagination): static
    {
        $this->defaultPagination = $pagination;
        return $this;
    }

    /**
     * Determine if the object has any default pagination.
     */
    public function hasDefaultPagination(): bool
    {
        return !empty($this->defaultPagination);
    }

    /**
     * Append one or more query filters on this object.
     *
     * @overrides CollectionLoader::setFilters()
     *
     * @param  array $filters An array of filters.
     */
    #[\Override]
    public function setFilters(array $filters, $trusted = true): static
    {
        if ($this->hasDefaultFilters()) {
            $this->addFilters($filters, $trusted);
        } else {
            parent::setFilters($filters, $trusted);
        }

        return $this;
    }

    /**
     * Append one or more query orders on this object.
     *
     * @overrides CollectionLoader::setOrders()
     *
     * @param  array $orders An array of orders.
     */
    #[\Override]
    public function setOrders(array $orders, $trusted = true): static
    {
        if ($this->hasDefaultOrders()) {
            $this->addOrders($orders, $trusted);
        } else {
            parent::setOrders($orders, $trusted);
        }

        return $this;
    }
}
