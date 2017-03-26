<?php

namespace QueryTranslator\Languages\Galach\Generators\ExtendedDisMax;

use LogicException;
use QueryTranslator\Languages\Galach\Values\Node\LogicalOr as LogicalOrNode;
use QueryTranslator\Values\Node;

/**
 * LogicalOr operator Node Visitor implementation.
 */
final class LogicalOr extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof LogicalOrNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        if (!$node instanceof LogicalOrNode) {
            throw new LogicException(
                'Visitor implementation accepts instance of LogicalOr Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clauses = [
            $subVisitor->visit($node->leftOperand, $subVisitor),
            $subVisitor->visit($node->rightOperand, $subVisitor),
        ];

        return implode(' OR ', $clauses);
    }
}
