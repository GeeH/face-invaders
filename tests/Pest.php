<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against the full Laravel application. Unit tests stay
| framework-free so they run fast.
|
*/

pest()->extend(TestCase::class)->in('Feature');
