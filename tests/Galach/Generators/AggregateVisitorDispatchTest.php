<?php

namespace QueryTranslator\Tests\Galach\Generators;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\Generators\Common\Aggregate;
use QueryTranslator\Values\Node;
use RuntimeException;

/**
 * Test case for Aggregate visitor.
 */
class AggregateVisitorDispatchTest extends TestCase
{
    public function testAccept()
    {
        /** @var \QueryTranslator\Values\Node $nodeMock */
        $nodeMock = $this->getMockBuilder(Node::class)->getMock();

        $this->assertTrue((new Aggregate())->accept($nodeMock));
    }

    public function testVisitThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No visitor available for Mock');

        /** @var \QueryTranslator\Values\Node $nodeMock */
        $nodeMock = $this->getMockBuilder(Node::class)->getMock();

        (new Aggregate())->visit($nodeMock);
    }
}
