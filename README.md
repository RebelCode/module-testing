# RebelCode - Modular Testing

[![Latest Stable Version](https://poser.pugx.org/rebelcode/modular-testing/version)](https://packagist.org/packages/rebelcode/modular-testing)

A set of tools - such as custom test cases, assertions, mock helpers and class stubs - that aid the testing of
RebelCode modules. Also includes a test generator for quickly generating tests for modules!

# Installation

Simply install the package with Composer, ideally as a developer dependency.

```
composer require --dev rebelcode/module-testing
```

# Custom Test Case

The `ModuleTestCase` is an extended PHPUnit test case that provides all of the helper functionality in this package.
To use, simply extend it for your test case:

```php
<?php

use RebelCode\Modular\Testing\ModuleTestCase;

class MyTest extends ModuleTestCase
{
    // ...
}
```

# Helper methods

`createModule($fqn, $key, $deps)`

Creates a module instance for the module with the given FQN (`$fqn`), key (`$key`) and dependencies (`$deps`), as well
as mocked config, container and composite container factories. The module is also initialized with a mocked event
manager and mocked event factory, if it requires them.

## Assertion Helpers

`assertModuleHasConfig($key, $value, $module)`

Asserts that a module provides a config entry for the key `$key` and that the value is equal to `$value`.

`assertModuleHasService($key, $type, $module, $deps)`

Asserts that a module provides a service with the key `$key` and that it is an instance of `$type`.
Third-party dependency services may be provided, as `$deps`, in the form of an array of declarations.  

## Mock Helpers

`mockContainer($data)` and `mockContainerFactory()`

These helper methods create mock container instances and mock container factory instances respectively.
The container mock is a simple implementation that can instantiate and cache services if given callable values.
The factory mock creates these instances on `make()`.

`mockConfigFactory($data)`

Creates mock config factory instances. The factories use `mockContainer` to create the instances on `make()`.

`mockCompositeContainer($containers)` and `mockCompositeContainerFactory()`

Similar to the previous helper mock methods, these create mock composite containers and factories for them.
The mock composite container implementation simply returns values from the first matching child container.

`mockEventManager()`

Creates a zero-functionality event manager mock instance. Expectations and method mocking can still be attached to it
in your test. 

`mockEvent($name, $params)` and `mockEventFactory()`

Creates a mock event instance with the given name (`$name`) and parameters (`$params`).
Creates a mock event factory instance that creates mock event instances on `make()`.

`mockInterface($fqn, $methods)`

Generic mocking utility method for creating a mock instance for a particular interface with the given FQN (`$fqn`) and
methods (`$methods`).

# Test Generator

The test generator script is available in your `vendor/bin` directory after installing.
It is **very* important* to run this script from the module package's root directory, since the script will attempt to
read the `config.php` and `services.php` files in that directory, and use them to generate the test case.

```
vendor/bin/rcmod-gen-test
```

By default, the generated test case is outputted to the relative `test/functional` directory as `ModuleTest.php`.
This may be configured using the `-o` argument. The namespace of the test and the module name are also configurable: 

```
vendor/bin/rcmod-gen-test -o "MyTest.php" --namespace="Custom\FuncTest" --module="FooBar\MyModule"
```

## Service type assertions

The generator can deduce the expected type of a service from the `services.php` file and generate assertions that
assert whether the service provided by the module is an instance of that type.

This is done by looking at the service's declaration doc comment, i.e. `/** ... */`, and extracting the value of the
the `@return` tag in the doc comment. The this value is then mapped to the FQN, if necessary, by referring to the `use`
statements in the file. Aliased `use` statements are also detected.

```php
<?php

// Sample services.php file

use RebelCode\WordPress\Nonce;
use Dhii\Event\EventFactory as EF;
use Psr\Container\ContainerInterface;

return [
    /**
     * Works! 
     *
     * @return Nonce;
     */
    'nonce' =>  function (ContainerInterface $c) {
       return Nonce();
    },
    /**
     * Alias Works!
     *
     * @return EF
     */
     'event_factory' => function (ContainerInterface $c) {
        return EF();
     },
     /**
      * No return tag - this service will not be type-tested
      *
      * @return EF
      */
      'event_factory_2' => function (ContainerInterface $c) {
         return EF();
      },
      /*
       * Not a doc comment - this service will not be type-tested
       *
       * @return EF
       */
      'event_factory_3' => function (ContainerInterface $c) {
         return EF();
      },
];
```
