<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use LogicException;
use QueryTranslator\Languages\Galach\Values\Node\LogicalAnd;
use QueryTranslator\Languages\Galach\Values\Node\LogicalOr as LogicalOrNode;
use QueryTranslator\Values\Node;

/**
 * BinaryOperator operator Node Visitor implementation.
 */
final class BinaryOperator extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof LogicalAnd || $node instanceof LogicalOrNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        if (!$node instanceof LogicalAnd && !$node instanceof LogicalOrNode) {
            throw new LogicException(
                'Visitor implementation accepts instance of LogicalAnd or LogicalOr Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Visitor implementation requires sub-visitor');
        }

        $clauses = [
            $subVisitor->visit($node->leftOperand, $subVisitor),
            $subVisitor->visit($node->rightOperand, $subVisitor),
        ];

        return implode(" {$node->token->lexeme} ", $clauses);
    }
}
