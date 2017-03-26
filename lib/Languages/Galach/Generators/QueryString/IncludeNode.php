<?php

namespace QueryTranslator\Languages\Galach\Generators\QueryString;

use LogicException;
use QueryTranslator\Languages\Galach\Values\Node\IncludeNode as IncludeNodeNode;
use QueryTranslator\Values\Node;

/**
 * Include operator Node Visitor implementation.
 */
final class IncludeNode extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof IncludeNodeNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        if (!$node instanceof IncludeNodeNode) {
            throw new LogicException(
                'Visitor implementation accepts instance of Include Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clause = $subVisitor->visit($node->operand, $subVisitor);

        return "+{$clause}";
    }
}
