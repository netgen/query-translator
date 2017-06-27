<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\Common;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\LogicalAnd as LogicalAndNode;
use QueryTranslator\Values\Node;

/**
 * LogicalAnd operator Node Visitor implementation.
 */
final class LogicalAnd extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof LogicalAndNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof LogicalAndNode) {
            throw new LogicException(
                'Implementation accepts instance of LogicalAnd Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clauses = [
            $subVisitor->visit($node->leftOperand, $subVisitor, $options),
            $subVisitor->visit($node->rightOperand, $subVisitor, $options),
        ];

        return implode(' AND ', $clauses);
    }
}
