Charcoal Model Collections / Repositories
=========================================

[![License][badge-license]][charcoal-model-collection]
[![Latest Stable Version][badge-version]][charcoal-model-collection]
[![Build Status][badge-travis]][dev-travis]

Support package providing advanced model collections and collection loaders for [Charcoal][charcoal-core] projects.



## Installation

```shell
composer require mcaskill/charcoal-model-collection
```

See [`composer.json`](composer.json) for depenencides.



## Collections

### 1. `Charcoal\Support\Model\Collection\Collection`

Provides methods to manipulate the collection or retrieve specific models.

#### `filter()`

Filter the collection of objects using the given callback.

```php
$collection = new Collection([ $a, $b, $c, $d, $e ]);
// [
//     1 => Model (active: 1), 2 => Model (active: 0),
//     3 => Model (active: 1), 4 => Model (active: 1),
//     5 => Model (active: 0)
// ]

$filtered = $collection->filter(function ($obj, $id) {
    return ($obj['active'] === true);
});
// [ 1 => Model, 3 => Model, 4 => Model ]
```

#### `forPage()`

"Paginate" the collection by slicing it into a smaller collection.

```php
$collection = new Collection([ $a, $b, $c, $d, $e, $f, $g, $h, $i, $j ]);
// [ 1 => Model, 2 => Model, 3 => Model, 4 => Model, 5 => Model,… ]

$chunk = $collection->forPage(2, 3);
// [ 4 => Model, 5 => Model, 6 => Model ]
```

#### `only()`

Extract the objects with the specified keys.

```php
$collection = new Collection([ $a, $b, $c, $d, $e ]);
// [ 1 => Model, 2 => Model, 3 => Model, 4 => Model, 5 => Model ]

$filtered = $collection->only(2);
// [ 2 => Model ]

$filtered = $collection->only([ 1, 3 ]);
// [ 1 => Model, 3 => Model ]

$filtered = $collection->only(2, 4);
// [ 2 => Model, 4 => Model ]
```

#### `pop()`

Remove and return the last object from the collection.

```php
$collection = new Collection([ $a, $b, $c, $d, $e ]);
// [ 1 => Model, 2 => Model, 3 => Model, 4 => Model, 5 => Model ]

$collection->pop();
// Model (5)

$collection->toArray();
// [ 1 => Model, 2 => Model, 3 => Model, 4 => Model ]
```

#### `prepend()`

Add an object onto the beginning of the collection.

```php
$collection = new Collection([ $a, $b, $c, $d, $e ]);
// [ 1 => Model, 2 => Model, 3 => Model, 4 => Model, 5 => Model ]

$collection->prepend($o);
// Model (15)

$filtered->toArray();
// [ 15 => Model, 1 => Model, 2 => Model, 3 => Model, 4 => Model, 5 => Model ]
```

#### `random()`

Retrieve one or more random objects from the collection.

```php
$collection = new Collection([ $a, $b, $c, $d, $e ]);
// [ 1 => Model, 2 => Model, 3 => Model, 4 => Model, 5 => Model ]

$collection->random();
// Model (3)

$collection->random(2);
// [ 1 => Model, 3 => Model ]
```

#### `reverse()`

Reverse the order of objects in the collection.

```php
$collection = new Collection([ $a, $b, $c, $d, $e ]);
// [ 1 => Model, 2 => Model, 3 => Model, 4 => Model, 5 => Model ]

$reversed = $collection->reverse();
// [ 5 => Model, 4 => Model, 3 => Model, 2 => Model, 1 => Model ]
```

#### `shift()`

Remove and return the first object from the collection.

```php
$collection = new Collection([ $a, $b, $c, $d, $e ]);
// [ 1 => Model, 2 => Model, 3 => Model, 4 => Model, 5 => Model ]

$collection->shift();
// Model (1)

$collection->toArray();
// [ 2 => Model, 3 => Model, 4 => Model, 5 => Model ]
```

#### `slice()`

Extract a slice of the collection.

```php
$collection = new Collection([ $a, $b, $c, $d, $e, $f, $g, $h, $i, $j ]);
// [ 1 => Model, 2 => Model, 3 => Model, 4 => Model, 5 => Model,… ]

$slice = $collection->slice(4);
// [ 5 => Model, 6 => Model, 7 => Model, 8 => Model, 9 => Model, 10 => Model ]

$slice = $collection->slice(4, 2);
// [ 5 => Model, 6 => Model ]
```

#### `sortBy()`

Sort the collection by the given callback or object property.

```php
$collection = new Collection([ $a, $b, $c ]);
// [ 1 => Model (position: 5), 2 => Model (position: 2), 3 => Model (position: 0) ]

$sorted = $collection->sortBy('position');
// [ 3 => Model, 2 => Model, 1 => Model ]
```

#### `sortByDesc()`

Sort the collection in descending order using the given callback or object property.

#### `take()`

Extract a portion of the first or last objects from the collection.

```php
$collection = new Collection([ $a, $b, $c, $d, $e, $f ]);
// [ 1 => Model, 2 => Model, 3 => Model, 4 => Model, 5 => Model, 6 => Model ]

$chunk = $collection->take(3);
// [ 1 => Model, 2 => Model, 3 => Model ]

$chunk = $collection->take(-2);
// [ 5 => Model, 6 => Model ]
```

#### `where()`

Filter the collection of objects by the given key/value pair.

```php
$collection = new Collection([ $a, $b, $c, $d, $e ]);
// [
//     1 => Model (active: 1), 2 => Model (active: 0),
//     3 => Model (active: 1), 4 => Model (active: 1),
//     5 => Model (active: 0)
// ]

$filtered = $collection->where('active', true);
// [ 1 => Model, 3 => Model, 4 => Model ]
```

#### `whereIn()`

Filter the collection of objects by the given key/value pair.

```php
$collection = new Collection([ $a, $b, $c, $d, $e ]);
// [
//     1 => Model (name: "Lorem"), 2 => Model (name: "Ipsum"),
//     3 => Model (name: "Dolor"), 4 => Model (name: "Elit"),
//     5 => Model (name: "Amet")
// ]

$filtered = $collection->whereIn('name', [ 'Amet', 'Dolor' ]);
// [ 3 => Model, 5 => Model ]
```



## Repositories

### 1. `Charcoal\Support\Model\Repository\CollectionLoaderIterator`

Provides improved counting of found rows (via `SQL_CALC_FOUND_ROWS`), supports PHP Generators via "cursor" methods, and supports chaining the loader directly into an iterator construct or appending additional criteria.


#### 1.1. Lazy Collections

The `CollectionLoaderIterator` leverages PHP's [generators][php-syntax-generators] to allow you to work with large collections while keeping memory usage low.

When using the traditional `load` methods, all models must be loaded into memory at the same time.

```php
$repository = (new CollectionLoaderIterator)->setModel(Post::class);

$repository->addFilter('active', true)->addFilter('published <= NOW()');

$posts = $repository->load();
// query: SELECT …
// array: Post, Post, Post,…

foreach ($posts as $post) {
    echo $post['title'];
}
```

However, the `cursor` methods return `Generator` objects instead. This allows you to keep one model loaded in memory at a time:

```php
$repository = (new CollectionLoaderIterator)->setModel(Post::class);

$repository->addFilter('active', true)->addFilter('published <= NOW()');

$posts = $repository->cursor();
// Generator

foreach ($posts as $post) { // query: SELECT …
    // Post
    echo $post['title'];
}
```


#### 1.2. `IteratorAggregate`

The `CollectionLoaderIterator` implements [`IteratorAggregate`][php-class-iterator-aggregate] which allows the repository to be used in a `foreach` construct without the need to explicitly call a query method.

```php
class Post extends AbstractModel
{
    /**
     * @return Comment[]|CollectionLoaderIterator
     */
    public function getComments() : iterable
    {
        $comments = (new CollectionLoaderIterator)->setModel(Comment::class);

        $byPost = [
            'property' => 'post_id',
            'value'    => $this['id'],
        ];

        return $comments->addFilter($byPost);
    }
}
```

Internally, the `IteratorAggregate::getIterator()` method calls the `CollectionLoaderIterator::cursor()` method which in turn returns a [`Generator`][php-class-generator] object.

```php
$post = $factory->create(Post::class)->load(1);

foreach ($post['comments'] as $comment) { // query: SELECT …
    // Comment
}
```

Furthermore, you can continue to chain constraints onto the repository:

```php
$post = $factory->create(Post::class)->load(1);

$comments = $post['comments']->addFilter('approved', true);
// CollectionLoaderIterator

foreach ($comments as $comment) { // query: SELECT …
    // Comment
}
```


#### 1.3. `SQL_CALC_FOUND_ROWS`

If the [`SQL_CALC_FOUND_ROWS`][mysql-function-found-rows] option is included in a `SELECT` statement, the `FOUND_ROWS()` function will be invoked afterwards to retrieve the number of objects the statement would have returned without the `LIMIT`.

Using the query builder interface, the generated statement will include `SQL_CALC_FOUND_ROWS` option unless the query is targeting a single object.

```php
$repository = (new CollectionLoaderIterator)->setModel(Post::class);

$repository->addFilter('active', true)
           ->addFilter('published <= NOW()')
           ->setNumPerPage(10)
           ->setPage(3);

// Automatically find total count from query builder
$posts = $repository->load();
// query: SELECT SQL_CALC_FOUND_ROWS * FROM `charcoal_users` WHERE ((`active` = '1') AND (`published` <= NOW())) LIMIT 30, 10;
// query: SELECT FOUND_ROWS();
$total = $repository->foundObjs();
// int: 38

// Automatically find total count from query
$users = $repository->reset()->loadFromQuery('SELECT SQL_CALC_FOUND_ROWS * … LIMIT 0, 20');
// query: SELECT SQL_CALC_FOUND_ROWS * … LIMIT 0, 20;
// query: SELECT FOUND_ROWS();
$total = $repository->foundObjs();
// int: 38

// Automatically find total count from query
$users = $repository->reset()->loadFromQuery('SELECT * … LIMIT 0, 20');
// query: SELECT * … LIMIT 0, 20;
$total = $repository->foundObjs();
// LogicException: Can not count found objects for the last query
```

### 2. `Charcoal\Support\Model\Repository\ModelCollectionLoader`

Provides support for cloning, preventing model swapping, and sharing the same [data source][charcoal-source-interface].

#### 2.1. Model Protection

Once a model is assigned to the `ModelCollectionLoader`, any attempts to replace it will result in a thrown exception:

```php
$repository = (new ModelCollectionLoader)->setModel(Post::class);

// …

$repository->setModel(Comment::class);
// RuntimeException: A model is already assigned to this collection loader: \App\Model\Post
```

On its own, this feature is not very practical but in concert with the `ScopedCollectionLoader` this becomes an important safety measure.


#### 2.2. Collection Loader Cloning

When cloning the `ModelCollectionLoader` via the `clone` keyword or the `cloneWith()` method, the model protection mechanism will be unlocked until a new object type is assigned or until the `source()` method is called.

```php
$postsLoader    = (new ModelCollectionLoader)->setModel(Post::class);
$commentsLoader = (clone $postsLoader)->setModel(Comment::class);
```

```php
$postsLoader    = (new ModelCollectionLoader)->setModel(Post::class);
$commentsLoader = $postsLoader->cloneWith(Comment::class);
$tagsLoader     = $postsLoader->cloneWith([
    'model'      => Tag::class,
    'collection' => 'array',
]);
```


#### 2.3. Source Sharing

A Charcoal Model is based on the ActiveRecord implementation for working with data sources; which is to say a Model allows you to interact with data in your database. This interaction is facilitated by a "Data Source" interface, like the `DatabaseSource` class. Each instance of a Model will usually create its own instance of a Data Source object; in other words, you end up always working with two objects per Model (the Model and the Data Source).

To reduce the number of objects in a request's lifecycle, its a good practice to assign a single instance of a Data Source to all Models. When the `ModelCollectionLoader` creates a new instance of the Model being queried, it will assign the _prototype_ Model's Data Source object (the one that is queried upon by the repository).

```php
$posts = (new BaseCollectionLoader)->setModel(Post::class)->load();
// array: Post, Post, Post,…

($posts[0]->source() === $posts[2]->source())
// bool: false

$posts = (new ModelCollectionLoader)->setModel(Post::class)->load();
// array: Post, Post, Post,…

($posts[0]->source() === $posts[2]->source())
// bool: true
```


### 3. `Charcoal\Support\Model\Repository\ScopedCollectionLoader`

Provides support for default filters, orders, and pagination, which are automatically applied upon the loader's creation and after every reset.

```php
$repository = new ScopedCollectionLoader([
    'logger'          => $container['logger'],
    'factory'         => $container['model/factory'],
    'model'           => Post::class,
    'default_filters' => [
        [
            'property' => 'active',
            'value'    => true,
        ],
        [
            'property' => 'publish_date',
            'operator' => 'IS NOT NULL',
        ],
    ],
    'default_orders'  => [
        [
            'property'  => 'publish_date',
            'direction' => 'desc',
        ],
    ],
    'default_pagination' => [
        'num_per_page' => 20,
    ],
]);

$posts = $repository->addFilter('publish_date <= NOW()')->load();
// query: SELECT SQL_CALC_FOUND_ROWS * FROM `posts` WHERE ((`active` = '1') AND (`publish_date` IS NOT NULL) AND (`published` <= NOW())) ORDER BY `publish_date` DESC LIMIT 20;

$repository->reset()->load();
// query: SELECT SQL_CALC_FOUND_ROWS * FROM `posts` WHERE ((`active` = '1') AND (`publish_date` IS NOT NULL)) ORDER BY `publish_date` DESC LIMIT 20;
```

If you would like to disable the default criteria on a repository, you may use the `withoutDefaults` method. The method accepts a callback to interact with collection loader if, for example, you only wish to apply default orders:

```php
$repository = new ScopedCollectionLoader([…]);

$posts = $repository->withoutDefaults(function () {
    $this->applyDefaultOrders();
    $this->applyDefaultPagination();
})->load();
// query: SELECT SQL_CALC_FOUND_ROWS * FROM `posts` ORDER BY `publish_date` DESC LIMIT 20;
```


### 4. `Charcoal\Support\Model\Repository\CachedCollectionLoader`

Provides support for storing the data of loaded models in a [cache pool][charcoal-cache], similar to the [`\Charcoal\Model\Service\ModelLoader`][charcoal-model-loader] and using the same cache key for  interoperability.

```php
$repository = new CachedCollectionLoader([
    'cache'   => $container['cache'],
    'logger'  => $container['logger'],
    'factory' => $container['model/factory'],
    'model'   => Post::class,
]);
```

If you would like to disable the caching process on a repository, you may use the `withoutCache` method. The method accepts a callback to interact with collection loader:

```php
$repository = new CachedCollectionLoader([…]);

$posts = $repository->withoutCache()->cursor();
// Generator
```



## License

-   _Charcoal Model Collections and Repositories_ component is licensed under the MIT license. See [LICENSE](LICENSE) for details.
-   _Charcoal_ framework is licensed under the MIT license. See [LICENSE][license-charcoal] for details.



[charcoal-model-collection]:    https://packagist.org/packages/mcaskill/charcoal-model-collection
[charcoal-cache]:               https://packagist.org/packages/locomotivemtl/charcoal-cache
[charcoal-core]:                https://packagist.org/packages/locomotivemtl/charcoal-core
[charcoal-model-loader]:        https://github.com/locomotivemtl/charcoal-core/blob/master/src/Charcoal/Model/Service/ModelLoader.php
[charcoal-source-interface]:    https://github.com/locomotivemtl/charcoal-core/blob/master/src/Charcoal/Source/SourceInterface.php
[license-charcoal]:             https://github.com/locomotivemtl/charcoal-core/blob/master/LICENSE
[mysql-function-found-rows]:    https://dev.mysql.com/doc/refman/5.7/en/information-functions.html#function_found-rows
[php-syntax-generators]:        https://www.php.net/manual/en/language.generators.overview.php
[php-class-generator]:          https://php.net/class.Generator
[php-class-iterator-aggregate]: https://php.net/class.IteratorAggregate

[dev-travis]:         https://travis-ci.org/mcaskill/charcoal-model-collection
[badge-license]:      https://img.shields.io/packagist/l/mcaskill/charcoal-model-collection.svg?style=flat-square
[badge-version]:      https://img.shields.io/packagist/v/mcaskill/charcoal-model-collection.svg?style=flat-square
[badge-travis]:       https://img.shields.io/travis/mcaskill/charcoal-model-collection.svg?style=flat-square
