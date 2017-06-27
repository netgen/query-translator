<?php

namespace QueryTranslator\Languages\Galach\Generators\Common;

use QueryTranslator\Values\Node;
use RuntimeException;

/**
 * Common Aggregate Visitor implementation.
 */
final class Aggregate extends Visitor
{
    /**
     * @var \QueryTranslator\Languages\Galach\Generators\Common\Visitor[]
     */
    private $visitors = [];

    /**
     * Construct from the optional array of $visitors.
     *
     * @param \QueryTranslator\Languages\Galach\Generators\Common\Visitor[] $visitors
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
     * @param \QueryTranslator\Languages\Galach\Generators\Common\Visitor $visitor
     */
    public function addVisitor(Visitor $visitor)
    {
        $this->visitors[] = $visitor;
    }

    public function accept(Node $node)
    {
        return true;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        foreach ($this->visitors as $visitor) {
            if ($visitor->accept($node)) {
                return $visitor->visit($node, $this, $options);
            }
        }

        throw new RuntimeException('No visitor available for ' . get_class($node));
    }
}
