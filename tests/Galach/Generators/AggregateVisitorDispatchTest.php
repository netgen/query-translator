<?php

namespace QueryTranslator\Tests\Galach\Generators;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\Generators\Common\Aggregate;
use QueryTranslator\Values\Node;

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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No visitor available for Mock
     */
    public function testVisitThrowsException()
    {
        /** @var \QueryTranslator\Values\Node $nodeMock */
        $nodeMock = $this->getMockBuilder(Node::class)->getMock();

        (new Aggregate())->visit($nodeMock);
    }
}
