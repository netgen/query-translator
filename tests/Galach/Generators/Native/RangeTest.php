<?php

namespace QueryTranslator\Tests\Galach\Generators\Native;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Generators\Native\Range;
use QueryTranslator\Languages\Galach\Values\Node\Mandatory;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Range as RangeToken;
use QueryTranslator\Languages\Galach\Values\Token\Word;
use QueryTranslator\Values\Node;

class RangeTest extends TestCase
{
    /**
     * @var Visitor
     */
    public $visitor;

    protected function setUp()
    {
        $this->visitor = new Range();
    }

    public function acceptDataprovider()
    {
        return [
            [true, new Term(new RangeToken('[a TO b]', 0, '', 'a', 'b', 'inclusive'))],
            [false, new Term(new Word('word', 0, '', 'a'))],
        ];
    }

    /**
     * @param bool $expected
     * @param Node $token
     *
     * @dataProvider acceptDataprovider
     */
    public function testAccepts($expected, $node)
    {
        $this->assertSame($expected, $this->visitor->accept($node));
    }

    public function visitDataprovider()
    {
        return [
            ['[a TO b]', new Term(new RangeToken('[a TO b]', 0, '', 'a', 'b', 'inclusive'))],
            ['{a TO b}', new Term(new RangeToken('{a TO b}', 0, '', 'a', 'b', 'exclusive'))],
        ];
    }

    /**
     * @param string $expected
     * @param Node   $token
     *
     * @dataProvider visitDataprovider
     */
    public function testVisit($expected, $node)
    {
        $this->assertSame($expected, $this->visitor->visit($node));
    }

    public function visitWrongNodeDataprovider()
    {
        return [
            [new Mandatory()],
            [new Term(new Word('word', 0, '', 'a'))],
        ];
    }

    /**
     * @param string $expected
     * @param Node   $token
     *
     * @dataProvider visitWrongNodeDataprovider
     */
    public function testVisitWrongNodeFails($node)
    {
       $this->expectException(\LogicException::class);
       $this->visitor->visit($node);
    }

    public function testVisitUnknownTypeFails()
    {
        $this->expectException(\LogicException::class);
        $node = new Term(new RangeToken('{a TO b}', 0, '', 'a', 'b', 'unknown'));
        $this->visitor->visit($node);
    }
}
