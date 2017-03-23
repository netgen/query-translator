<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use QueryTranslator\Values\Node;
use RuntimeException;

/**
 * Aggregate Visitor implementation.
 */
final class Aggregate extends Visitor
{
    /**
     * @var \QueryTranslator\Languages\Galach\Generators\Native\Visitor[]
     */
    private $visitors;

    /**
     * Construct from the optional array of $visitors.
     *
     * @param \QueryTranslator\Languages\Galach\Generators\Native\Visitor[] $visitors
     */
    public function __construct(array $visitors = [])
    {
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
    }

    /**
     * Add a $visitor to the aggregated collection.
     *
     * @param \QueryTranslator\Languages\Galach\Generators\Native\Visitor $visitor
     */
    public function addVisitor(Visitor $visitor)
    {
        $this->visitors[] = $visitor;
    }

    public function accept(Node $node)
    {
        return true;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        foreach ($this->visitors as $visitor) {
            if ($visitor->accept($node)) {
                return $visitor->visit($node, $this);
            }
        }

        throw new RuntimeException('No visitor available for ' . get_class($node));
    }
}
