<?php

namespace QueryTranslator\Tests\Galach\Generators;

use LogicException;
use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Generators\Native\BinaryOperator;
use QueryTranslator\Languages\Galach\Generators\Native\Group;
use QueryTranslator\Languages\Galach\Generators\Native\Phrase;
use QueryTranslator\Languages\Galach\Generators\Native\Query;
use QueryTranslator\Languages\Galach\Generators\Native\Tag;
use QueryTranslator\Languages\Galach\Generators\Native\UnaryOperator;
use QueryTranslator\Languages\Galach\Generators\Native\User;
use QueryTranslator\Languages\Galach\Generators\Native\Word;
use QueryTranslator\Languages\Galach\Values\Node\Group as GroupNode;
use QueryTranslator\Languages\Galach\Values\Node\LogicalAnd as LogicalAndNode;
use QueryTranslator\Languages\Galach\Values\Node\LogicalNot as LogicalNotNode;
use QueryTranslator\Languages\Galach\Values\Node\LogicalOr as LogicalOrNode;
use QueryTranslator\Languages\Galach\Values\Node\Mandatory as MandatoryNode;
use QueryTranslator\Languages\Galach\Values\Node\Prohibited as ProhibitedNode;
use QueryTranslator\Languages\Galach\Values\Node\Query as QueryNode;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Values\Node;
use QueryTranslator\Values\Token;

/**
 * Test case for Native visitors.
 */
class NativeVisitorDispatchTest extends TestCase
{
    public function providerForTestVisitThrowsLogicExceptionNode()
    {
        $nodeMock = $this->getMockBuilder(Node::class)->getMock();

        return [
            [
                new BinaryOperator(),
                $nodeMock,
                'Implementation accepts instance of LogicalAnd or LogicalOr Node',
            ],
            [
                new Group(),
                $nodeMock,
                'Implementation accepts instance of Group Node',
            ],
            [
                new Phrase(),
                $nodeMock,
                'Implementation accepts instance of Term Node',
            ],
            [
                new Query(),
                $nodeMock,
                'Implementation accepts instance of Query Node',
            ],
            [
                new Tag(),
                $nodeMock,
                'Implementation accepts instance of Term Node',
            ],
            [
                new UnaryOperator(),
                $nodeMock,
                'Implementation accepts instance of Mandatory, Prohibited or LogicalNot Node',
            ],
            [
                new User(),
                $nodeMock,
                'Implementation accepts instance of Term Node',
            ],
            [
                new Word(),
                $nodeMock,
                'Implementation accepts instance of Term Node',
            ],
        ];
    }

    /**
     * @dataProvider providerForTestVisitThrowsLogicExceptionNode
     *
     * @param \QueryTranslator\Languages\Galach\Generators\Common\Visitor $visitor
     * @param \QueryTranslator\Values\Node $node
     * @param string $expectedExceptionMessage
     */
    public function testVisitThrowsLogicExceptionNode(Visitor $visitor, Node $node, $expectedExceptionMessage)
    {
        $this->expectException(LogicException::class);

        try {
            $visitor->visit($node);
        } catch (LogicException $e) {
            $this->assertSame($expectedExceptionMessage, $e->getMessage());
            throw $e;
        }
    }

    public function providerForTestVisitThrowsLogicExceptionToken()
    {
        /** @var \QueryTranslator\Values\Token $tokenMock */
        $tokenMock = $this->getMockBuilder(Token::class)->disableOriginalConstructor()->getMock();
        $node = new Term($tokenMock);

        return [
            [
                new Phrase(),
                $node,
                'Implementation accepts instance of Phrase Token',
            ],
            [
                new Tag(),
                $node,
                'Implementation accepts instance of Tag Token',
            ],
            [
                new User(),
                $node,
                'Implementation accepts instance of User Token',
            ],
            [
                new Word(),
                $node,
                'Implementation accepts instance of Word Token',
            ],
        ];
    }

    /**
     * @dataProvider providerForTestVisitThrowsLogicExceptionToken
     *
     * @param \QueryTranslator\Languages\Galach\Generators\Common\Visitor $visitor
     * @param \QueryTranslator\Values\Node $node
     * @param string $expectedExceptionMessage
     */
    public function testVisitThrowsLogicExceptionToken(Visitor $visitor, Node $node, $expectedExceptionMessage)
    {
        $this->expectException(LogicException::class);

        try {
            $visitor->visit($node);
        } catch (LogicException $e) {
            $this->assertSame($expectedExceptionMessage, $e->getMessage());
            throw $e;
        }
    }

    public function providerForTestVisitThrowsLogicExceptionSubVisitor()
    {
        return [
            [
                new BinaryOperator(),
                new LogicalAndNode(),
            ],
            [
                new BinaryOperator(),
                new LogicalOrNode(),
            ],
            [
                new Group(),
                new GroupNode(),
            ],
            [
                new Query(),
                new QueryNode([]),
            ],
            [
                new UnaryOperator(),
                new LogicalNotNode(),
            ],
            [
                new UnaryOperator(),
                new MandatoryNode(),
            ],
            [
                new UnaryOperator(),
                new ProhibitedNode(),
            ],
        ];
    }

    /**
     * @dataProvider providerForTestVisitThrowsLogicExceptionSubVisitor
     *
     * @param \QueryTranslator\Languages\Galach\Generators\Common\Visitor $visitor
     * @param \QueryTranslator\Values\Node $node
     */
    public function testVisitThrowsLogicExceptionSubVisitor(Visitor $visitor, Node $node)
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Implementation requires sub-visitor");

        $visitor->visit($node);
    }
}
