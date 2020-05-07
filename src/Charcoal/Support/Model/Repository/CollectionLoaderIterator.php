<?php

namespace Charcoal\Support\Model\Repository;

use PDO;
use ArrayIterator;
use IteratorAggregate;
use LogicException;
use InvalidArgumentException;
use RuntimeException;

// From 'illuminate/support'
use Illuminate\Support\Collection as LaravelCollection;

// From 'charcoal-core'
use Charcoal\Model\ModelInterface;
use Charcoal\Model\CollectionInterface;
use Charcoal\Loader\CollectionLoader as BaseCollectionLoader;

/**
 * Iterable Object Collection Loader
 *
 * Provides improved counting of found rows (via `SQL_CALC_FOUND_ROWS`),
 * supports PHP Generators via "cursor" methods, and supports chaining the loader
 * directly into an iterator construct or appending additional criteria.
 */
class CollectionLoaderIterator extends BaseCollectionLoader implements IteratorAggregate
{
    /**
     * Total number of objects found via `SQL_CALC_FOUND_ROWS`.
     *
     * @var integer|null
     */
    protected $foundObjs;

    /**
     * Track whether the last statement was executed
     * from the query builder or was a custom statement.
     *
     * @var boolean|null
     */
    protected $fromQueryBuilder;

    /**
     * Reset everything but the model.
     *
     * @return self
     */
    public function reset()
    {
        parent::reset();

        $this->foundObjs        = null;
        $this->fromQueryBuilder = null;

        return $this;
    }

    /**
     * Get the total number of items for the last query.
     *
     * @return integer|null
     */
    public function foundObjs()
    {
        if ($this->foundObjs === null) {
            $this->foundObjs = $this->loadFound();
        }

        return $this->foundObjs;
    }

    /**
     * Alias of {@see self::foundObjs()}.
     *
     * @return integer
     */
    public function loadCount()
    {
        return $this->foundObjs();
    }

    /**
     * Get the total number of items for this collection query.
     *
     * @overrides \Charcoal\Loader\CollectionLoader::loadCount()
     *     BREAKING: This method returns total number of items
     *     found matching the current query parameters.
     *
     * @param  boolean $withFoundRows If TRUE, uses `FOUND_ROWS()` otherwise runs the last query again.
     * @throws LogicException If the last statement can not lookup found rows.
     * @return integer
     */
    public function loadFound($withFoundRows = false)
    {
        if ($this->fromQueryBuilder === false) {
            throw new LogicException('Can not count found objects for the last query');
        }

        $src = $this->source();
        $dbh = $src->db();

        $sql = $withFoundRows ? 'SELECT FOUND_ROWS()' : $src->sqlLoadCount();
        $this->logger->debug($sql);

        $sth = $dbh->prepare($sql);
        $sth->execute();

        $count = (int)$sth->fetchColumn(0);
        return $count;
    }

    /**
     * Find models by the loader's current query and return a generator.
     *
     * @param  string|null   $ident     Optional. A pre-defined list to use from the model.
     * @param  callable|null $after     Process each entity after applying raw data.
     * @param  callable|null $before    Process each entity before applying raw data.
     * @param  integer       $foundObjs If provided, then it is filled with the number of found rows.
     * @return ModelInterface[]|\Generator
     */
    public function cursor(
        $ident = null,
        callable $after = null,
        callable $before = null,
        &$foundObjs = null
    ) {
        if ($ident !== null) {
            return $this->cursorOne($ident, $before, $after);
        }

        $source  = $this->source();
        $selects = $source->sqlSelect();
        $tables  = $source->sqlFrom();
        $filters = $source->sqlFilters();
        $orders  = $source->sqlOrders();
        $limits  = $source->sqlPagination();

        if ($limits && $this->numPerPage() !== 1) {
            $calcFoundRows = 'SQL_CALC_FOUND_ROWS ';
        } else {
            $calcFoundRows = '';
        }

        $this->fromQueryBuilder = true;

        $sql = 'SELECT ' . $calcFoundRows . $selects . ' FROM ' . $tables . $filters . $orders . $limits;
        $results = $this->cursorFromQuery($sql, $after, $before, $foundObjs);

        return $results;
    }

    /**
     * Find a model by its primary key and return a generator.
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

        $source = $this->source();
        $model  = $this->model();

        if ($id !== null) {
            $this->addFilter([
                'property' => $model->key(),
                'operator' => '=',
                'value'    => $id,
            ]);
        }

        $selects = $source->sqlSelect();
        $tables  = $source->sqlFrom();
        $filters = $source->sqlFilters();

        $sql = 'SELECT ' . $selects . ' FROM ' . $tables . $filters . ' LIMIT 1';

        $this->logger->debug($sql);
        $dbh = $source->db();
        $sth = $dbh->prepare($sql);
        $sth->execute();

        if ($sth->execute() !== false) {
            $objData = $sth->fetch(PDO::FETCH_ASSOC);
            if ($objData) {
                $obj = $this->processModel($objData, $before, $after);

                if ($obj instanceof ModelInterface) {
                    yield $obj;
                }
            }
        }
    }

    /**
     * Find multiple models by their primary keys and return a generator.
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

        $source = $this->source();
        $model  = $this->model();
        $key    = $model->key();

        $this->addFilter([
            'property' => $key,
            'operator' => 'IN',
            'values'   => $ids,
        ]);

        $this->addOrder([
            'property' => $key,
            'values'   => $ids,
        ]);

        $selects = $source->sqlSelect();
        $tables  = $source->sqlFrom();
        $filters = $source->sqlFilters();
        $orders  = $source->sqlOrders();

        $this->fromQueryBuilder = true;

        $sql = 'SELECT ' . $selects . ' FROM ' . $tables . $filters . $orders . ' LIMIT ' . count($ids);
        $results = $this->cursorFromQuery($sql, $after, $before);

        return $results;
    }

    /**
     * Find all models and return a generator.
     *
     * @param  callable $before Process each entity before applying raw data.
     * @param  callable $after  Process each entity after applying raw data.
     * @return ModelInterface[]|\Generator
     */
    public function cursorAll(callable $before = null, callable $after = null)
    {
        $source  = $this->source();
        $selects = $source->sqlSelect();
        $tables  = $source->sqlFrom();
        $filters = $source->sqlFilters();
        $orders  = $source->sqlOrders();

        $this->fromQueryBuilder = true;

        $sql = 'SELECT ' . $selects . ' FROM ' . $tables . $filters . $orders;
        $results = $this->cursorFromQuery($sql, $after, $before);

        return $results;
    }

    /**
     * Find models by the given query and return a generator.
     *
     * @param  string|array  $query     The SQL query as a string or an array composed of the query,
     *     parameter binds, and types of parameter bindings.
     * @param  callable|null $callback  Process each entity after applying raw data.
     *    Leave blank to use {@see CollectionLoader::callback()}.
     * @param  callable|null $before    Process each entity before applying raw data.
     * @param  integer|null  $foundObjs If provided, then it is filled with the number of found rows.
     * @throws InvalidArgumentException If the SQL string/set is invalid.
     * @return ModelInterface[]|\Generator
     */
    public function cursorFromQuery(
        $query,
        callable $callback = null,
        callable $before = null,
        &$foundObjs = null
    ) {
        $source = $this->source();

        $dbh = $source->db();

        /** @todo Filter binds */
        if (is_string($query)) {
            $query = trim($query);
            $this->logger->debug($query);
            $sth = $dbh->prepare($query);
            $sth->execute();
        } elseif (is_array($query)) {
            list($query, $binds, $types) = array_pad($query, 3, []);
            $query = trim($query);

            $sth = $source->dbQuery($query, $binds, $types);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The SQL query must be a string or an array: ' .
                '[ string $query, array $binds, array $dataTypes ]; ' .
                'received %s',
                is_object($query) ? get_class($query) : $query
            ));
        }

        if ($this->fromQueryBuilder === null) {
            $this->fromQueryBuilder = false;
        }

        $wasCalcFoundRows = strpos($query, 'SELECT SQL_CALC_FOUND_ROWS') === 0;
        if ($wasCalcFoundRows) {
            $this->foundObjs = $this->loadFound(true);
            $foundObjs = $this->foundObjs;
        }

        $sth->setFetchMode(PDO::FETCH_ASSOC);

        if ($callback === null) {
            $callback = $this->callback();
        }

        yield from $this->processCursor($sth, $before, $callback);
    }

    /**
     * Process the collection of raw data.
     *
     * @param  mixed[]|Traversable $results The raw result set.
     * @param  callable|null       $before  Process each entity before applying raw data.
     * @param  callable|null       $after   Process each entity after applying raw data.
     * @return ModelInterface[]|\Generator
     */
    protected function processCursor($results, callable $before = null, callable $after = null)
    {
        foreach ($results as $objData) {
            $obj = $this->processModel($objData, $before, $after);

            if ($obj instanceof ModelInterface) {
                yield $obj;
            }
        }
    }

    /**
     * Find model(s) in datasource.
     *
     * @param  array    $filters   One or many filters.
     * @param  callable $callback  Process each entity after applying raw data.
     * @param  callable $before    Process each entity before applying raw data.
     * @param  integer  $foundObjs If provided, then it is filled with the number of found rows.
     * @return ModelInterface[]
     */
    public function findBy(
        array $filters = [],
        callable $callback = null,
        callable $before = null,
        &$foundObjs = null
    ) {
        $this->addFilters($filters);

        return $this->load(null, $callback, $before, $foundObjs);
    }

    /**
     * Find the immediate sibling of the given model in the datasource.
     *
     * @param  ModelInterface $model     The model to lookup.
     * @param  string         $direction Either "<" for the left tree value or ">" for the right tree value.
     * @param  string         $sortKey   The property identifier for sorting. Defaults to "position".
     * @param  string         $groupKey  The property identifier for subsetting. Defaults to "master".
     * @param  callable       $before    Process each model before applying raw data.
     * @param  callable       $after     Process each model after applying raw data.
     * @throws InvalidArgumentException If $model or $direction are invalid.
     * @return ModelInterface|null
     */
    public function findOneAdjacentTo(
        ModelInterface $model,
        string $direction,
        string $sortKey = 'position',
        string $groupKey = null,
        callable $before = null,
        callable $after = null
    ) {
        if (!$sortKey || !$model->hasProperty($sortKey)) {
            throw new InvalidArgumentException('Model must have a sorting property');
        }

        if ($groupKey && !$model->hasProperty($groupKey)) {
            throw new InvalidArgumentException('Model does not have a grouping property');
        }

        if ($direction === '<' || $direction === 'lft' || $direction === 'left') {
            $direction = '<';
            $order     = 'DESC';
        } elseif ($direction === '>' || $direction === 'rgt' || $direction === 'right') {
            $direction = '>';
            $order     = 'ASC';
        } else {
            throw new InvalidArgumentException('Invalid adjacency direction');
        }

        $key = $model->key();

        $filters = [];
        $orders  = [
            [
                'property'  => $sortKey,
                'direction' => $order,
            ],
            [
                'property'  => $key,
                'direction' => $order,
            ],
        ];

        if ($groupKey !== null) {
            $grouping = $model[$groupKey];
            if ($grouping instanceof ModelInterface) {
                $grouping = $grouping['id'];
            }

            if (!is_scalar($grouping)) {
                throw new InvalidArgumentException('Model should have a valid grouping property value');
            }

            if (!$grouping) {
                $filters[] = [
                    'property' => $groupKey,
                    'operator' => '=',
                    'value'    => $grouping,
                ];
            } else {
                $filters[] = [
                    'property' => $groupKey,
                    'operator' => 'IS NULL',
                ];
            }
        }

        $filters[] = [
            'property' => $sortKey,
            'operator' => $direction,
            'value'    => $model[$sortKey],
        ];

        // Force reset sorting only
        parent::setOrders([]);

        // Append adjacency sorting
        $this->setOrders($orders);

        // Append adjacency predicates
        $this->addFilters($filters);

        $source = $this->source();
        $model  = $this->model();

        $selects = $source->sqlSelect();
        $tables  = $source->sqlFrom();
        $filters = $source->sqlFilters();
        $orders  = $source->sqlOrders();

        $sql = 'SELECT ' . $selects . ' FROM ' . $tables . $filters . $orders . ' LIMIT 1';

        $this->logger->debug($sql);
        $dbh = $source->db();
        $sth = $dbh->prepare($sql);
        $sth->execute();

        if ($sth->execute() === false) {
            return null;
        }

        $data = $sth->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            return $this->processModel($data, $before, $after);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @overrides \Charcoal\Loader\CollectionLoader::load()
     *     This method adds support for `SQL_CALC_FOUND_ROWS`
     *     and repurposes the first parameter.
     *
     * @param  mixed    $ident     The model identifier.
     * @param  callable $callback  Process each entity after applying raw data.
     * @param  callable $before    Process each entity before applying raw data.
     * @param  integer  $foundObjs If provided, then it is filled with the number of found rows.
     * @return ModelInterface[]
     */
    public function load(
        $ident = null,
        callable $callback = null,
        callable $before = null,
        &$foundObjs = null
    ) {
        if ($ident !== null) {
            return $this->loadOne($ident, $before, $callback);
        }

        $source  = $this->source();
        $selects = $source->sqlSelect();
        $tables  = $source->sqlFrom();
        $filters = $source->sqlFilters();
        $orders  = $source->sqlOrders();
        $limits  = $source->sqlPagination();

        if ($limits && $this->numPerPage() !== 1) {
            $calcFoundRows = 'SQL_CALC_FOUND_ROWS ';
        } else {
            $calcFoundRows = '';
        }

        $this->fromQueryBuilder = true;

        $sql = 'SELECT ' . $calcFoundRows . $selects . ' FROM ' . $tables . $filters . $orders . $limits;
        $results = $this->loadFromQuery($sql, $callback, $before, $foundObjs);

        return $results;
    }

    /**
     * Find a model by its primary key.
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

        $source = $this->source();
        $model  = $this->model();

        if ($id !== null) {
            $this->addFilter([
                'property' => $model->key(),
                'operator' => '=',
                'value'    => $id,
            ]);
        }

        $selects = $source->sqlSelect();
        $tables  = $source->sqlFrom();
        $filters = $source->sqlFilters();

        $sql = 'SELECT ' . $selects . ' FROM ' . $tables . $filters . ' LIMIT 1';

        $this->logger->debug($sql);
        $dbh = $source->db();
        $sth = $dbh->prepare($sql);
        $sth->execute();

        if ($sth->execute() === false) {
            return null;
        }

        $data = $sth->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            return $this->processModel($data, $before, $after);
        }

        return null;
    }

    /**
     * Find multiple models by their primary keys.
     *
     * @param  array    $ids    One or many model identifiers.
     * @param  callable $before Process each entity before applying raw data.
     * @param  callable $after  Process each entity after applying raw data.
     * @throws InvalidArgumentException If the $ids do not resolve to a queryable statement.
     * @return ModelInterface[]
     */
    public function loadMany(array $ids, callable $before = null, callable $after = null)
    {
        if (!$this->areIdsValid($ids)) {
            throw new InvalidArgumentException('At least one model ID is required');
        }

        $source = $this->source();
        $model  = $this->model();
        $key    = $model->key();

        $this->addFilter([
            'property' => $key,
            'operator' => 'IN',
            'values'   => $ids,
        ]);

        $this->addOrder([
            'property' => $key,
            'values'   => $ids,
        ]);

        $selects = $source->sqlSelect();
        $tables  = $source->sqlFrom();
        $filters = $source->sqlFilters();
        $orders  = $source->sqlOrders();

        $this->fromQueryBuilder = true;

        $sql = 'SELECT ' . $selects . ' FROM ' . $tables . $filters . $orders . ' LIMIT ' . count($ids);
        $results = $this->loadFromQuery($sql, $after, $before);

        return $results;
    }

    /**
     * Find all models.
     *
     * @param  callable $before Process each entity before applying raw data.
     * @param  callable $after  Process each entity after applying raw data.
     * @return ModelInterface[]
     */
    public function loadAll(callable $before = null, callable $after = null)
    {
        $source  = $this->source();
        $selects = $source->sqlSelect();
        $tables  = $source->sqlFrom();
        $filters = $source->sqlFilters();
        $orders  = $source->sqlOrders();

        $this->fromQueryBuilder = true;

        $sql = 'SELECT ' . $selects . ' FROM ' . $tables . $filters . $orders;
        $results = $this->loadFromQuery($sql, $after, $before);

        return $results;
    }

    /**
     * {@inheritdoc}
     *
     * @overrides \Charcoal\Loader\CollectionLoader::loadFromQuery()
     *     This method adds support for `SQL_CALC_FOUND_ROWS`.
     *
     * @param  string|array  $query     The SQL query as a string or an array composed of the query,
     *     parameter binds, and types of parameter bindings.
     * @param  callable|null $callback  Process each entity after applying raw data.
     *    Leave blank to use {@see CollectionLoader::callback()}.
     * @param  callable|null $before    Process each entity before applying raw data.
     * @param  integer|null  $foundObjs If provided, then it is filled with the number of found rows.
     * @throws InvalidArgumentException If the SQL string/set is invalid.
     * @return ModelInterface[]
     */
    public function loadFromQuery(
        $query,
        callable $callback = null,
        callable $before = null,
        &$foundObjs = null
    ) {
        $source = $this->source();

        $dbh = $source->db();

        /** @todo Filter binds */
        if (is_string($query)) {
            $query = trim($query);
            $this->logger->debug($query);
            $sth = $dbh->prepare($query);
            $sth->execute();
        } elseif (is_array($query)) {
            list($query, $binds, $types) = array_pad($query, 3, []);
            $query = trim($query);

            $sth = $source->dbQuery($query, $binds, $types);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The SQL query must be a string or an array: ' .
                '[ string $query, array $binds, array $dataTypes ]; ' .
                'received %s',
                is_object($query) ? get_class($query) : $query
            ));
        }

        if ($this->fromQueryBuilder === null) {
            $this->fromQueryBuilder = false;
        }

        $wasCalcFoundRows = strpos($query, 'SELECT SQL_CALC_FOUND_ROWS') === 0;
        if ($wasCalcFoundRows) {
            $this->foundObjs = $this->loadFound(true);
            $foundObjs = $this->foundObjs;
        }

        $sth->setFetchMode(PDO::FETCH_ASSOC);

        if ($callback === null) {
            $callback = $this->callback();
        }

        return $this->processCollection($sth, $before, $callback);
    }

    /**
     * Create a collection from the given value.
     *
     * @param  mixed $value The value being converted.
     * @throws RuntimeException If the collection class is invalid.
     * @return array|ArrayAccess
     */
    public function createCollectionWith($value)
    {
        $collectClass = $this->collectionClass();
        if ($collectClass === 'array') {
            if (is_array($value)) {
                return $value;
            } elseif (class_exists('\Illuminate\Support\Collection') && $value instanceof LaravelCollection) {
                return $value->all();
            } elseif ($value instanceof CollectionInterface) {
                return $value->all();
            } elseif ($value instanceof Traversable) {
                return iterator_to_array($value);
            } elseif ($value instanceof ModelInterface) {
                return [ $value ];
            }

            return (array)$value;
        }

        return new $collectClass($value);
    }

    /**
     * Get an iterator for the collection.
     *
     * This method will {@see CollectionLoader::load() load the results}
     * using the current criteria.
     *
     * @return \Generator
     */
    public function getIterator()
    {
        return $this->cursor();
    }

    /**
     * Determine whether a variable is a valid ID (number or string).
     *
     * @return boolean
     */
    protected function isIdValid($id)
    {
        if (is_numeric($id)) {
            return ((int)$id > 0);
        }

        if (is_string($id)) {
            return isset($id[0]);
        }

        return false;
    }

    /**
     * Determine whether a list contains valid IDs (numbers or strings).
     *
     * @return boolean
     */
    protected function areIdsValid(array $ids)
    {
        if (empty($ids)) {
            return false;
        }

        foreach ($ids as $id) {
            if (!$this->isIdValid($id)) {
                return false;
            }
        }

        return true;
    }
}
