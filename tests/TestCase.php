<?php

namespace Rushing\Graphine\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * The framework-free base for php-graphine's suite. The core is testbench-free:
 * every test here operates over in-memory / array sources, so a plain PHPUnit
 * TestCase is all the graph algebra needs.
 */
abstract class TestCase extends BaseTestCase
{
    //
}
