<?php

namespace QueryTranslator\Languages\Galach\Generators\ExtendedDisMax;

use LogicException;
use QueryTranslator\Languages\Galach\Values\Node\Exclude as ExcludeNode;
use QueryTranslator\Values\Node;

/**
 * Exclude operator Node Visitor implementation.
 */
final class Exclude extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof ExcludeNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        if (!$node instanceof ExcludeNode) {
            throw new LogicException(
                'Visitor implementation accepts instance of Exclude Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clause = $subVisitor->visit($node->operand, $subVisitor);

        return "-{$clause}";
    }
}
