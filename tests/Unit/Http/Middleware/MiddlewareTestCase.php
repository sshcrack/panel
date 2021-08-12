<?php

namespace Kriegerhost\Tests\Unit\Http\Middleware;

use Kriegerhost\Tests\TestCase;
use Kriegerhost\Tests\Traits\Http\RequestMockHelpers;
use Kriegerhost\Tests\Traits\Http\MocksMiddlewareClosure;
use Kriegerhost\Tests\Assertions\MiddlewareAttributeAssertionsTrait;

abstract class MiddlewareTestCase extends TestCase
{
    use MiddlewareAttributeAssertionsTrait;
    use MocksMiddlewareClosure;
    use RequestMockHelpers;

    /**
     * Setup tests with a mocked request object and normal attributes.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->buildRequestMock();
    }
}
