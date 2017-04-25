<?php

namespace QueryTranslator\Tests\Galach\Tokenizer;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\Values\Node\Group;
use QueryTranslator\Languages\Galach\Values\Node\LogicalAnd;
use QueryTranslator\Languages\Galach\Values\Node\LogicalNot;
use QueryTranslator\Languages\Galach\Values\Node\LogicalOr;
use QueryTranslator\Languages\Galach\Values\Node\Mandatory;
use QueryTranslator\Languages\Galach\Values\Node\Prohibited;
use QueryTranslator\Languages\Galach\Values\Node\Query;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Values\Token;
use QueryTranslator\Values\Node;

/**
 * Test case for node tree traversal.
 */
class NodeTraversalTest extends TestCase
{
    public function testGroupNode()
    {
        $firstMember = $this->getMockForAbstractClass(Node::class);
        $secondMember = $this->getMockForAbstractClass(Node::class);

        $nodes = (new Group([$firstMember, $secondMember]))->getNodes();

        $this->assertSame($firstMember, $nodes[0]);
        $this->assertSame($secondMember, $nodes[1]);
    }

    public function testLogicalAndNode()
    {
        $leftOperand = $this->getMockForAbstractClass(Node::class);
        $rightOperand = $this->getMockForAbstractClass(Node::class);

        $nodes = (new LogicalAnd($leftOperand, $rightOperand))->getNodes();

        $this->assertSame($leftOperand, $nodes[0]);
        $this->assertSame($rightOperand, $nodes[1]);
    }

    public function testLogicalNotNode()
    {
        $operand = $this->getMockForAbstractClass(Node::class);

        $nodes = (new LogicalNot($operand))->getNodes();

        $this->assertSame($operand, $nodes[0]);
    }

    public function testLogicalOrNode()
    {
        $leftOperand = $this->getMockForAbstractClass(Node::class);
        $rightOperand = $this->getMockForAbstractClass(Node::class);

        $nodes = (new LogicalOr($leftOperand, $rightOperand))->getNodes();

        $this->assertSame($leftOperand, $nodes[0]);
        $this->assertSame($rightOperand, $nodes[1]);
    }

    public function testMandatoryNode()
    {
        $operand = $this->getMockForAbstractClass(Node::class);

        $nodes = (new Mandatory($operand))->getNodes();

        $this->assertSame($operand, $nodes[0]);
    }

    public function testProhibitedNode()
    {
        $operand = $this->getMockForAbstractClass(Node::class);

        $nodes = (new Prohibited($operand))->getNodes();

        $this->assertSame($operand, $nodes[0]);
    }

    public function testQueryNode()
    {
        $firstMember = $this->getMockForAbstractClass(Node::class);
        $secondMember = $this->getMockForAbstractClass(Node::class);

        $nodes = (new Query([$firstMember, $secondMember]))->getNodes();

        $this->assertSame($firstMember, $nodes[0]);
        $this->assertSame($secondMember, $nodes[1]);
    }

    public function testTermNode()
    {
        /** @var \QueryTranslator\Values\Token $token */
        $token = $this->getMockBuilder(Token::class)->disableOriginalConstructor()->getMock();

        $nodes = (new Term($token))->getNodes();

        $this->assertEmpty($nodes);
    }
}
