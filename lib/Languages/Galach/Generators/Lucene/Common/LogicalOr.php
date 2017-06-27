<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\Common;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
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

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof LogicalOrNode) {
            throw new LogicException(
                'Implementation accepts instance of LogicalOr Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clauses = [
            $subVisitor->visit($node->leftOperand, $subVisitor, $options),
            $subVisitor->visit($node->rightOperand, $subVisitor, $options),
        ];

        return implode(' OR ', $clauses);
    }
}
