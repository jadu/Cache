# Zodyac Cache

Caching library with a unified interface for multiple backends

[![Latest Stable Version](https://poser.pugx.org/zodyac/cache/v/stable.png)](https://packagist.org/packages/zodyac/cache) [![Build Status](https://secure.travis-ci.org/jadu/Cache.png)](http://travis-ci.org/jadu/Cache)

## Usage

```php
use Zodyac\Cache\Cache;
use Zodyac\Cache\Storage\ApcStorage;

$cache = new Cache(new ApcStorage());
$result = $cache->get('key');
if ($result->isMiss()) {
    $value = expensiveOperation(42);
    $cache->set('key', $value);
}
```

## Backends

### Memcached
### APC

## Doctrine cache bridge

Use the your `Zodyac\Cache\Cache` instance as a Doctrine Cache with `Zodyac\Cache\DoctrineCache`. This is useful when you want to use Zodyac Cache for metadata, query or result caching in [Doctrine ORM](http://github.com/doctrine/orm).

```php
use Zodyac\Cache\Cache;
use Zodyac\Cache\DoctrineCache;
use Zodyac\Cache\Storage\ApcStorage;

$cache = new Cache(new ApcStorage());
$doctrineCache = new DoctrineCache($cache);
$value = $doctrineCache->doFetch('key');
```

## Credits

* [Tom Graham](http://github.com/noginn) - Project lead
