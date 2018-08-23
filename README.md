# RebelCode - Modular Testing

[![Build Status](https://travis-ci.org/rebelcode/modular-testing.svg?branch=master)](https://travis-ci.org/rebelcode/modular-testing)
[![Code Climate](https://codeclimate.com/github/rebelcode/modular-testing/badges/gpa.svg)](https://codeclimate.com/github/rebelcode/modular-testing)
[![Test Coverage](https://codeclimate.com/github/rebelcode/modular-testing/badges/coverage.svg)](https://codeclimate.com/github/rebelcode/modular-testing/coverage)
[![Latest Stable Version](https://poser.pugx.org/rebelcode/modular-testing/version)](https://packagist.org/packages/rebelcode/modular-testing)

A set of tools - such as custom test cases, common mocking and class stubs - that aid the testing of RebelCode modules.
Also includes a test generator for quickly generating tests for modules.

# Custom Test Case

The `ModuleTestCase` is an extended PHPUnit test case that provides mocking functionality that is common for testing RebelCode modules.

To use, simply extend it for your test case:

```php
<?php

use RebelCode\Modular\Testing\ModuleTestCase;

class MyTest extends ModuleTestCase
{
    // ...
}
```

## Helper methods

`createModule($fqn, $key, $deps)`

Creates a module instance for the module with the given FQN (`$fqn`), key (`$key`) and dependencies (`$deps`), as well
as mocked config, container and composite container factories. The module is also initialized with a mocked event
manager and mocked event factory, if it requires them. 

`mockContainer($data)`

Creates a mocked container instance, that can also instantiate and cache services if given callable values.  

`mockCompositeContainer($containers)`

Creates a mocked composite container instance that simply returns values from the first matching child container.

`mockEventManager`

Creates a zero-functionality event manager mock instance. Expectations and method mocking can still be attached to it
in your test. 

`mockEvent($name, $params)`

Creates a mock event instance with the given name (`$name`) and parameters (`$params`).

`mockEventFactory`

Creates a mock event factory instance that creates mock event instances on `make()`.

`mockContainerFactory`

Creates a mock container factory instance that creates mock container instances on `make()`.

`mockCompositeContainerFactory`

Creates a mock composite container factory instance that creates mock composite container instances on `make()`.

`mockConfigFactory`

Creates a mock config factory instance that creates mock container instances on `make()`.

`mockInterface($fqn, $methods)`

Generic mocking utility method for creating a mock instance for a particular interface with the given FQN (`$fqn`) and
methods (`$methods`).

## Test Generator

The test generator script is available in your `vendor/bin` directory after installing.
It is **very* important* to run this script from the module package's root directory.

```
vendor/bin/rcmod-gen-test
```

This will scan the module directory for the `config.php` and `services.php` files, read them and generate the test case.
The namespace and module name used in the test case may be configured using arguments:

```
vendor/bin/rcmod-gen-test --namespace="RebelCode\Custom\FuncTest" --module="RebelCode\Custom\MyModule"
```

By default, the generated test case is outputted to the relative `test/functional` directory as `ModuleTest.php`.
This can also be configured using an argument:

```
vendor/bin/rcmod-gen-test -o "test/unit/GeneratedTest.php"
```
