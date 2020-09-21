<?php

namespace Charcoal\Support\Model\Repository;

use InvalidArgumentException;

// From 'charcoal-cache'
use Charcoal\Cache\CachePoolAwareTrait;

// From 'charcoal-core'
use Charcoal\Model\ModelInterface;

/**
 * Cached Object Collection Loader
 *
 * Provides support for storing loaded models in a cache pool
 * (compatible with the {@see \Charcoal\Model\Service\ModelLoader}).
 */
class CachedCollectionLoader extends ScopedCollectionLoader
{
    use CachePoolAwareTrait;

    /**
     * The prefix for the cache key.
     *
     * @var string
     */
    private $cacheKeyPrefix;

    /**
     * Track whether collection loader should use the cache.
     *
     * @var boolean
     */
    private $useCache = true;

    /**
     * Return a new CollectionLoader object.
     *
     * @param array $data The loader's dependencies.
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->setCachePool($data['cache']);
    }

    /**
     * Clone the collection loader.
     *
     * @param  mixed $data An array of customizations for the clone or an object model.
     * @return static
     */
    public function cloneWith($data)
    {
        if (!is_array($data)) {
            $data = [
                'model' => $data,
            ];
        }

        $data['cache'] = $this->cachePool();

        return parent::cloneWith($data);
    }

    /**
     * Disable the cache for the duration of the query.
     *
     * @param  callable|null $callback A callback bound to the collection loader.
     * @return self
     */
    public function withoutCache(callable $callback = null)
    {
        $this->useCache = false;

        if ($callback !== null) {
            $callback = Closure::bind($callback, $this, get_class($this));
            $callback();
        }

        return $this;
    }



    // Cursor
    // -------------------------------------------------------------------------

    /**
     * Find a model by its primary key from the cache pool or from the data source
     * and return a generator.
     *
     * @param  mixed    $id     The model identifier.
     * @param  callable $before Process each entity before applying raw data.
     * @param  callable $after  Process each entity after applying raw data.
     * @throws InvalidArgumentException If the $id does not resolve to a queryable statement.
     * @return ModelInterface|\Generator
     */
    public function cursorOne($id = null, callable $before = null, callable $after = null)
    {
        if ($id !== null && !$this->isIdValid($id)) {
            throw new InvalidArgumentException('One model ID is required');
        }

        if ($this->useCache && $id !== null) {
            $model = $this->getModelFromCache($id);
            if ($model !== null) {
                yield $model;
            }
        }

        yield from parent::cursorOne($id, $before, $after);

        $this->useCache = true;
    }

    /**
     * Find multiple models by their primary keys from the cache pool or from the data source
     * and return a generator.
     *
     * @param  array    $ids    One or many model identifiers.
     * @param  callable $before Process each entity before applying raw data.
     * @param  callable $after  Process each entity after applying raw data.
     * @throws InvalidArgumentException If the $ids do not resolve to a queryable statement.
     * @return ModelInterface[]|\Generator
     */
    public function cursorMany(array $ids, callable $before = null, callable $after = null)
    {
        if (!$this->areIdsValid($ids)) {
            throw new InvalidArgumentException('At least one model ID is required');
        }

        if ($this->useCache) {
            yield from $this->cursorManyFromCache($ids, $before, $after);
            return;
        }

        yield from parent::cursorMany($ids, $before, $after);

        $this->useCache = true;
    }

    /**
     * Find multiple models by their primary keys from the cache pool
     * and return a generator.
     *
     * @param  array    $ids    One or many model identifiers.
     * @param  callable $before Process each entity before applying raw data.
     * @param  callable $after  Process each entity after applying raw data.
     * @throws InvalidArgumentException If the $ids do not resolve to a queryable statement.
     * @return ModelInterface[]|\Generator
     */
    public function cursorManyFromCache(array $ids, callable $before = null, callable $after = null)
    {
        if (!$this->areIdsValid($ids)) {
            throw new InvalidArgumentException('At least one model ID is required');
        }

        $hitsById = [];
        foreach ($ids as $id) {
            $hitsById[$id] = $this->hasModelInCache($id);
        }

        $misses = array_keys($hitsById, false, true);
        if (empty($misses)) {
            foreach ($ids as $id) {
                yield $this->getModelFromCache($id);
            }
            return;
        }

        $missing = parent::cursorMany($misses, $before, $after);
        foreach ($hitsById as $id => $hit) {
            if ($hit) {
                yield $this->getModelFromCache($id);
            } elseif ($missing->valid()) {
                yield $missing->current();
                $missing->next();
            }
        }
    }



    // Query
    // -------------------------------------------------------------------------

    /**
     * Find a model by its primary key from the cache pool or from the data source.
     *
     * @param  mixed    $id     The model identifier.
     * @param  callable $before Process each entity before applying raw data.
     * @param  callable $after  Process each entity after applying raw data.
     * @throws InvalidArgumentException If the $id does not resolve to a queryable statement.
     * @return ModelInterface|null
     */
    public function loadOne($id = null, callable $before = null, callable $after = null)
    {
        if ($id !== null && !$this->isIdValid($id)) {
            throw new InvalidArgumentException('One model ID is required');
        }

        if ($this->useCache && $id !== null) {
            $model = $this->getModelFromCache($id);
            if ($model !== null) {
                return $model;
            }
        }

        $model = parent::loadOne($id, $before, $after);

        $this->useCache = true;

        return $model;
    }

    /**
     * Find multiple models by their primary keys from the cache pool or from the data source.
     *
     * @param  array    $ids    One or many model identifiers.
     * @param  callable $before Process each entity before applying raw data.
     * @param  callable $after  Process each entity after applying raw data.
     * @throws InvalidArgumentException If the $ids do not resolve to a queryable statement.
     * @return ModelInterface[]|ArrayAccess
     */
    public function loadMany(array $ids, callable $before = null, callable $after = null)
    {
        if (!$this->areIdsValid($ids)) {
            throw new InvalidArgumentException('At least one model ID is required');
        }

        if ($this->useCache) {
            return $this->loadManyFromCache($ids, $before, $after);
        }

        $models = parent::loadMany($ids, $before, $after);

        $this->useCache = true;

        return $models;
    }

    /**
     * Find multiple models by their primary keys from the cache pool.
     *
     * @param  array    $ids    One or many model identifiers.
     * @param  callable $before Process each entity before applying raw data.
     * @param  callable $after  Process each entity after applying raw data.
     * @throws InvalidArgumentException If the $ids do not resolve to a queryable statement.
     * @return ModelInterface[]|ArrayAccess
     */
    public function loadManyFromCache(array $ids, callable $before = null, callable $after = null)
    {
        if (!$this->areIdsValid($ids)) {
            throw new InvalidArgumentException('At least one model ID is required');
        }

        $models = [];
        foreach ($ids as $id) {
            $model = $this->getModelFromCache($id);
            $models[$id] = $model;
        }

        $misses = array_keys($models, null, true);
        if (empty($misses)) {
            $models = array_values($models);
            return $this->createCollectionWith($models);
        }

        $missing = parent::loadMany($misses, $before, $after);
        foreach ($missing as $model) {
            $models[$model['id']] = $model;
        }

        $models = array_filter($models, 'is_object');
        $models = array_values($models);
        return $this->createCollectionWith($models);
    }

    /**
     * {@inheritdoc}
     *
     * @overrides CollectionLoader::processModel()
     *
     * @param  mixed         $objData The raw dataset.
     * @param  callable|null $before  Process each entity before applying raw data.
     * @param  callable|null $after   Process each entity after applying raw data.
     * @return ModelInterface|null
     */
    protected function processModel($objData, callable $before = null, callable $after = null)
    {
        $obj = parent::processModel($objData, $before, $after);

        if ($this->useCache && ($obj instanceof ModelInterface)) {
            $this->addModelToCache($obj);
        }

        return $obj;
    }



    // Cache
    // -------------------------------------------------------------------------

    /**
     * Fetch a model from the cache.
     *
     * @param  mixed $id The model identifier.
     * @return ModelInterface|null
     */
    protected function getModelFromCache($id)
    {
        $pool = $this->cachePool();
        $key  = $this->getModelCacheKey($id);
        $item = $pool->getItem($key);

        if ($item->isHit()) {
            $data  = $item->get();
            $model = $this->createModelFromData($data);
            $model->setData($data);

            return $model;
        }

        return null;
    }

    /**
     * Add a model to the cache.
     *
     * @param  ModelInterface $model The model to store.
     * @throws InvalidArgumentException If the model is invalid.
     * @return void
     */
    protected function addModelToCache(ModelInterface $model)
    {
        $id = $model['id'];

        if (empty($id)) {
            throw new InvalidArgumentException('Model must have an ID');
        }

        $data = $model->data();
        if (!is_array($data)) {
            throw new InvalidArgumentException('Model must return a dataset');
        }

        $pool = $this->cachePool();
        $key  = $this->getModelCacheKey($id);
        $item = $pool->getItem($key);

        $item->set($data);
        $pool->save($item);
    }

    /**
     * Determines whether a model is present in the cache.
     *
     * @param  mixed $id The model identifier.
     * @return boolean
     */
    protected function hasModelInCache($id)
    {
        $pool = $this->cachePool();
        $key  = $this->getModelCacheKey($id);
        $item = $pool->getItem($key);

        return $item->isHit();
    }

    /**
     * Generate a model loader cache key.
     *
     * @param  mixed $id The model identifier to hash.
     * @throws InvalidArgumentException If the $id does not resolve to a queryable statement.
     * @return string
     */
    private function getModelCacheKey($id)
    {
        if ((empty($id) || !is_scalar($id))) {
            throw new InvalidArgumentException('Invalid model ID');
        }

        if ($this->cacheKeyPrefix === null) {
            $model = $this->model();
            $this->cacheKeyPrefix = 'object/' . str_replace('/', '.', $model::objType() . '.' . $model->key());
        }

        return $this->cacheKeyPrefix . '.' . str_replace('/', '.', $id);
    }
}
