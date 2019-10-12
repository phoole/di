# di
[![Build Status](https://travis-ci.com/phoole/di.svg?branch=master)](https://travis-ci.com/phoole/di)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phoole/di/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phoole/di/?branch=master)
[![Code Climate](https://codeclimate.com/github/phoole/di/badges/gpa.svg)](https://codeclimate.com/github/phoole/di)
[![PHP 7](https://img.shields.io/packagist/php-v/phoole/di)](https://packagist.org/packages/phoole/di)
[![Latest Stable Version](https://img.shields.io/github/v/release/phoole/di)](https://packagist.org/packages/phoole/di)
[![License](https://img.shields.io/github/license/phoole/di)]()

**phoole/di** is a slim and powerful [PSR-11][PSR-11] implementation of dependency injection 
library for PHP.

It builds upon the versatile [phoole/config][config] library and supports
[object decorating](#decorate), [object scope](#scope) and more. It requires PHP 7.2+. It is
compliant with [PSR-1][PSR-1], [PSR-4][PSR-4], [PSR-11][PSR-11] and [PSR-12][PSR-12].

[PSR-1]: http://www.php-fig.org/psr/psr-1/ "PSR-1: Basic Coding Standard"
[PSR-4]: http://www.php-fig.org/psr/psr-4/ "PSR-4: Autoloader"
[PSR-11]: http://www.php-fig.org/psr/psr-11/ "Container Interface"
[PSR-12]: http://www.php-fig.org/psr/psr-12/ "Extended Coding Style Guide"
[config]: https://github.com/phoole/config "phoole/config"

Installation
---
Install via the `composer` utility.

```
composer require "phoole/di=1.*"
```

or add the following lines to your `composer.json`

```json
{
    "require": {
       "phoole/di": "1.*"
    }
}
```

Usage
---

- With configuration from files or array

  ```php
  use Phoole\Di\Container;
  use Phoole\Config\Config;

  $configData = [
      // service definitions
      'di.service' => [
          // cache service
          'cache'  => ['class' => 'MyCache', 'args' => ['${#driver}']],

          // cache driver, classname directly
          'driver' => 'MyCacheDriver',
      ],

      // common methods to run after each object creation
      'di.common' => [
          [
            function($obj) { return $obj instanceof \MyCacheDriver; }, // tester
            function($obj, $container) { echo "ok"; } // runner
          ],
      ]
  ];

  // inject config into container
  $container = new Container(new Config($configData));

  // get service by id 'cache' (di.service.cache)
  $cache = $container->get('cache');

  // true
  var_dump($cache instanceof \MyCache);
  ```

  Container related configurations are under the node `di` and service definitions
  are under the `di.service` node.

Features
---

- <a name="ref"></a>**References**

  References in the form of '${reference}' can be used to refer to predefined
  parameters from the config or services in the container.

  **Characters of `'$', '{', '}', '.'` are not allowed in reference name**.
  Characters of `'#', '@'` have special meanings, such that should not be part
  of *normal* service names.

  - <a name="pref"></a>Parameter references

    See [phoole/config reference](https://github.com/phoole/config#ref) for
    detail. Parameter references are read from configuration files or array.

  - <a name="sref"></a>Service references

    Service reference in the form of '${#serviceId}' can be used to referring
    a service instance in the container.

    **Two reserved service references are '${#container}' and '${#config}'**.
    These two are refering the container instance itself and the config instance
    it is using. These two can be used just like other service references.

  - Using references

    References can be used anywhere in the configuration.

    ```php
    $confData = [
        'di.common' => [
            [['${#logger}', 'notice'], ['object created using ${log.facility}']]
    ]];
    ```

- <a name="decorate"></a>**Object decorating**

  *Object decorating* is to apply decorating changes (executing methods etc.)
  right after the instantiation of a service instance.

  - Decorating methods for *individual instance* only

    ```php
    $container->set('cache', [
        'class'   => 'Phoole\\Cache\\Cache',
        'args'    => ['${#cachedriver}'], // constructor arguments
        'after' => [
            ['clearCache'], // method of $cache
            ['setLogger', ['${#logger}']], // method with arguments
            [[$logger, 'setLabel'], ['cache_label']], // callable with arguments
            [['${#driver}, 'init']], // pseduo callable
            // ...
        ],
    ]);
    ```

    By adding `after` section into the `cache` service definition in the
    form of `[ callableOrMethodName, OptionalArgumentArray ]`, these methods
    will be executed right after `cache` instantiation.

    `callableOrMethodName` here can be,

    - method name of current instance

    - a valid callable

    - a psedudo callable with references (after resolving the references, it is
      a valid callable).

    `OptionalArgumentArray` here can be,

    - empty

    - array of values or references

  - Common decorating methods for *all instances*

    ```php
    $configData = [
        // common methods for all instances
        'di.common' => [
            // [ tester(): bool, method ]
            [
                function($object, $container) {
                    return $object instanceof 'Psr\\Log\\LoggerAwareInterface'
                },
                ['setLogger', ['${#logger}']]
            ],
            // ...
        ],
    ];
    ```

    Common methods can be configured in the 'di.common' node to apply to all the
    instances right after their instantiation. The definition consists of two
    parts, the first is a tester callable takes current instance and the
    container as parameters and returns a boolean value. The second part is in
    the same method format as in the service definition 'after'.

- <a name="scope"></a>**Object scope**

  - Shared or single scope

    By default, service instances in the container are shared inside the
    container. If users want different instance each time, they may just
    add '@' to the service id.

    ```php
    // cache service by default is in shared scope
    $cache1 = $container->get('cache');

    // get again
    $cache2 = $container->get('cache');

    // same
    var_dump($cache1 === $cache2); // true

    // a new cache instance
    $cache3 = $container->get('cache@');

    // different instances
    var_dump($cache1 !== $cache3); // true

    // but both share the same cacheDriver dependent service
    var_dump($cache1->getDriver() === $cache3->getDriver()); // true
    ```

  - Object scope

    You may get an instance in your own scope as follows

    ```php
    // no scope
    $cache1 = $container->get('cache@myScope');

    // in `myScope`
    $cache2 = $container->get('cache@myScope');

    // different instances
    var_dump($cache1 !== $cache2); // true

    // shared in myScope
    $cache3 = $container->get('cache@myScope');
    var_dump($cache2 === $cache2); // true
    ```

    Service references can also have scope defined as follows,

    ```php
    $container->set('cache', [
        'class' => 'Phossa2\\Cache\\Cache',
        'args'  => ['${#driver@myScope}'] // use driver of myScope
    ]);
    ```
APIs
---

- <a name="api"></a>Container related

  - `get(string $id): object` from *ContainerInterface*

  - `has(string $id): bool` from *ContainerInterface*

    `$id` may have '@scope' appended.

Testing
---

```bash
$ composer test
```

Dependencies
---

- PHP >= 7.2.0

- phoole/config >= 1.0.8

License
---

- [Apache 2.0](https://www.apache.org/licenses/LICENSE-2.0)
