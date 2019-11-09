# di
[![Build Status](https://travis-ci.com/phoole/di.svg?branch=master)](https://travis-ci.com/phoole/di)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phoole/di/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phoole/di/?branch=master)
[![Code Climate](https://codeclimate.com/github/phoole/di/badges/gpa.svg)](https://codeclimate.com/github/phoole/di)
[![PHP 7](https://img.shields.io/packagist/php-v/phoole/di)](https://packagist.org/packages/phoole/di)
[![Latest Stable Version](https://img.shields.io/github/v/release/phoole/di)](https://packagist.org/packages/phoole/di)
[![License](https://img.shields.io/github/license/phoole/di)]()

A slim, powerful and standalone [PSR-11][PSR-11] implementation of dependency injection 
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
composer require "phoole/di"
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

- With configuration from files or definition array

  ```php
  use Phoole\Di\Container;
  use Phoole\Config\Config;
  use Phoole\Cache\Cache;
  use Phoole\Cache\Adaptor\FileAdaptor;

  $configData = [
      // service definitions
      'di.service' => [
          // classname & constructor arguments
          'cache'  => [
              'class' => Cache::class,
              'args' => ['${#cacheDriver}'] // optional
          ],

          // use classname directly
          'cacheDriver' => FileAdaptor::class
      ],

      // methods to run after each object initiation
      'di.after' => [
          // a callable, takes THE object as parameter
          function($obj) { echo "ok"; },
  
          // will be converted to $obj->setLogger($logger)
          'setLogger',
      ]
  ];

  // inject configurations into container
  $container = new Container(new Config($configData));

  // get service by id 'cache' (di.service.cache)
  $cache = $container->get('cache');
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

  - <a name="pref"></a>Parameter references `${system.tempdir}`

    ```php
    $config = [
        ...
        // use predefined 'sytem.tmpdir' in arguments etc.
        'di.service.cacheDriver' => [
            'class' => FileAdaptor::class,
            'args'  => ['${system.tmpdir}'],
        ],
        ...
    ];
    ```
  
    See [phoole/config reference](https://github.com/phoole/config#ref) for
    detail. Parameter references are read from configuration files or array.
  
  - <a name="sref"></a>Service references

    Service object reference in the form of `${#serviceId}` can be used to referring
    a service instance in the container.

    ```php
    $configData = [
      ...
      'di.service' => [
          'cache'  => [
              'class' => Cache::class,
              'args' => ['${#cacheDriver}'] // object reference
          ],
          'cacheDriver' => ...
      ...
    ```
    
    Two reserved service references are **`${#container}`** and **`${#config}`**.
    These two are referring the container instance itself and the config instance
    it is using. These two can be used just like other service references.

  - Using references

    References can be used anywhere in the configuration.

    ```php
    $confData = [
        // methods executed after ALL object initiation
        'di.after' => [
            [['${#logger}', 'notice'], ['object created using ${log.facility}']]
        ]
    ];
    ```

- <a name="decorate"></a>**Object decorating**

  *Object decorating* is to apply decorating changes (executing methods etc.)
  right before or after the instantiation of a service instance.

  - Decorating methods for **individual instance** only

    ```php
    $config = [
       'di.service' => [
           ...
           'cache', [
               'class'  => '${cache.class}',
               'args'   => ['${#cachedriver}'], // constructor arguments
               'before' => [
                   [['${#logger}', 'info'], ['before initiating cache']], // $logger->info(...)
               ],
               'after'  => [
                   'clearCache', // $cache->clearCache() method
                   ['setLogger', ['${#logger}']], // $cache->setLogger($logger), argument is optional
                   [['${#logger}', 'info'], ['just a info']], // $logger->info(...)
                   function($cache) { // a callable takes object in parameter

                   }, 
               ]
           ],
           ...
       ]
    ];
    ```

    By adding `before` or `after` section into the `cache` service definition in the
    form of `[callableOrMethodName, OptionalArgumentArray]`, these methods will be 
    executed right before/after `cache` instantiation.

    `callableOrMethodName` here can be,

    - method name of initiated object

      ```php
      ...
        'after' => [
            // $obj->setLogger($logger), $logger will be injected automatically
            'setLogger', // object implementing 'LoggerAwareInterface'
        ],
      ...
      ```
      
    - a valid callable which takes initiated object as parameter
    
      ```php
       ...
         'after' => [
             // callable takes initiated object as parameter
             function($obj) {
             },
         ],
       ...
      ```
    - a pseudo callable with references (after resolving the references, it is
      a valid callable).

      ```php
       ...
         'after' => [
             // a pseudo callable with references
             [['${#logger}', 'info'], ['just a info']], // $logger->info(...)
         ],
       ...
      ```
      
    `OptionalArgumentArray` here can be,

      - empty

      - array of values or references

  - Common decorating methods for **all instances**

    ```php
    $configData = [
        // before all instances initiated
        'di.before' => [
            [['${#logger}', 'info'], ['before create']],
        ],
        // after methods for all instances
        'di.after' => [
            ['setLogger', ['${#logger}']], // arguments are optional
            'setDispatcher',  // simple enough, set event dispatcher
        ],
    ];
    ```

    Common methods can be configured in the 'di.before' or 'di.after' node to apply
    to all the instances right before or after their instantiation.

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

    // get a NEW cache instance
    $cache3 = $container->get('cache@');

    // different instances
    var_dump($cache1 !== $cache3); // true

    // but both share the same cacheDriver dependent service
    var_dump($cache1->getAdaptor() === $cache3->getAdaptor()); // true
    ```

  - Object scope
  
    You may get an instance in your own scope as follows

    ```php
    // no scope
    $cache1 = $container->get('cache');

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
        'class' => Cache::class,
        'args'  => ['${#driver@myScope}'] // use driver of myScope
    ]);
    ```

- <a name="static"></a>**Static access**
  
  - Access predefined services statically
  
    Objects in the container can also be access through a static way. Couple of 
    names are reserved. e.g. `get` and `has`.
    
    ```php
    // after container initiated
    // equals to $cache = $container->get('cache')
    $cache = Container::cache();
    
    // if myservice defined and invokable
    $obj = Container::myservice('test');
    ``` 
  - Initiating object by taking advantage or dependency injection
  
    ```php
    use Phoole\Cache\Cache;
    use Psr\Log\LoggerAwareTrait;
    use Psr\Log\LoggerAwareInterface;
    
    class MyClass implements LoggerAwareInterface
    {
         use LoggerAwareTrait;
    
         public function __construct(Cache $cache)
         {
         }
    }
    
    // $cache will be injected automatically
    // also 'setLogger' will be executed if defined in '.after' section
    $obj = Container::create(MyClass::class);
    ```

- <a name="autowiring"></a>**Autowiring** and **Injection**
  
  - Automatically parameter resolving (autowiring)
    
    Parameters of a constructor/callable will be resolved by looking
   
    - exists in the classmap (service objects created already) ?
   
    - classname known to the script (class defined but not in container configs) ?
  
  - Auto injection
  
    Instead of using 'annotation', we encourage of using `*AwareInterface` for your
    own classes' dependency injection.
    
    ```php
    use Psr\Log\LoggerAwareTrait;
    use Psr\Log\LoggerAwareInterface;
    
    class MyOwnClass implements LoggerAwareInterface
    {
         use LoggerAwareTrait;
         ...
    }
    
    // create your object with arguments
    $obj = Container::create(MyOnwClass::class, [...]);
    ```
    
    `Container` has all the common injection predefined in the `di.after` section
    
    ```php
    $config = [
    
        'di.after' => [
            'setLogger',        // logger aware
            'setCache',         // cache aware
            'setDispatcher',    // event aware
            'setContainer',     // container aware
            ...
        ],
    ];
    
    $container = new Container(new Config());
    ...
    ```

- <a name="aware"></a>**`ContainerAWareInterface`**

  Both `ContainerAWareInterface` and `ContainerAWareTrait` available. 

APIs
---

- <a name="api"></a>Container related

  - `get(string $id): object` from *ContainerInterface*

  - `has(string $id): bool` from *ContainerInterface*

    `$id` may have `@` or `@scope` appended.

Testing
---

```bash
$ composer test
```

Dependencies
---

- PHP >= 7.2.0

- phoole/config >= 1.*

License
---

- [Apache 2.0](https://www.apache.org/licenses/LICENSE-2.0)
