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
