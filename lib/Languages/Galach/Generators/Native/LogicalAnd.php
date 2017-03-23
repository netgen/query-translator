<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

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

    public function visit(Node $logicalAnd, Visitor $subVisitor = null)
    {
        /** @var \QueryTranslator\Languages\Galach\Values\Node\LogicalAnd $logicalAnd */
        $clauses = [
            $subVisitor->visit($logicalAnd->leftOperand, $subVisitor),
            $subVisitor->visit($logicalAnd->rightOperand, $subVisitor),
        ];

        return implode(" {$logicalAnd->token->lexeme} ", $clauses);
    }
}
