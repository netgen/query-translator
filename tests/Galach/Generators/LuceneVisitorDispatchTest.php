<?php

namespace QueryTranslator\Tests\Galach\Generators;

use LogicException;
use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Group;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\LogicalAnd;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\LogicalNot;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\LogicalOr;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Mandatory;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Phrase;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Prohibited;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Query;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Tag;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\User;
use QueryTranslator\Languages\Galach\Generators\Lucene\ExtendedDisMax\Word as ExtendedDisMaxWord;
use QueryTranslator\Languages\Galach\Generators\Lucene\QueryString\Word as QueryStringWord;
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
 * Test case for Lucene visitors.
 */
class LuceneVisitorDispatchTest extends TestCase
{
    public function providerForTestVisitThrowsLogicExceptionNode()
    {
        $nodeMock = $this->getMockBuilder(Node::class)->getMock();

        return [
            [
                new Group(),
                $nodeMock,
                'Implementation accepts instance of Group Node',
            ],
            [
                new LogicalAnd(),
                $nodeMock,
                'Implementation accepts instance of LogicalAnd Node',
            ],
            [
                new LogicalNot(),
                $nodeMock,
                'Implementation accepts instance of LogicalNot Node',
            ],
            [
                new LogicalOr(),
                $nodeMock,
                'Implementation accepts instance of LogicalOr Node',
            ],
            [
                new Mandatory(),
                $nodeMock,
                'Implementation accepts instance of Mandatory Node',
            ],
            [
                new Phrase(),
                $nodeMock,
                'Implementation accepts instance of Term Node',
            ],
            [
                new Prohibited(),
                $nodeMock,
                'Implementation accepts instance of Prohibited Node',
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
                new User(),
                $nodeMock,
                'Implementation accepts instance of Term Node',
            ],
            [
                new ExtendedDisMaxWord(),
                $nodeMock,
                'Implementation accepts instance of Term Node',
            ],
            [
                new QueryStringWord(),
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
                new ExtendedDisMaxWord(),
                $node,
                'Implementation accepts instance of Word Token',
            ],
            [
                new QueryStringWord(),
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
                new Group(),
                new GroupNode(),
            ],
            [
                new LogicalAnd(),
                new LogicalAndNode(),
            ],
            [
                new LogicalNot(),
                new LogicalNotNode(),
            ],
            [
                new LogicalOr(),
                new LogicalOrNode(),
            ],
            [
                new Mandatory(),
                new MandatoryNode(),
            ],
            [
                new Prohibited(),
                new ProhibitedNode(),
            ],
            [
                new Query(),
                new QueryNode([]),
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
