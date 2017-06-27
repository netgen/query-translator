<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\Common;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\Prohibited as ProhibitedNode;
use QueryTranslator\Values\Node;

/**
 * Prohibited operator Node Visitor implementation.
 */
final class Prohibited extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof ProhibitedNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof ProhibitedNode) {
            throw new LogicException(
                'Implementation accepts instance of Prohibited Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clause = $subVisitor->visit($node->operand, $subVisitor, $options);

        return "-{$clause}";
    }
}
