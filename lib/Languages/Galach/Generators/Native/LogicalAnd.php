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

    public function visit(Node $logicalAnd, Visitor $visitor = null)
    {
        /** @var \QueryTranslator\Languages\Galach\Values\Node\LogicalAnd $logicalAnd */
        $clauses = [
            $visitor->visit($logicalAnd->leftOperand, $visitor),
            $visitor->visit($logicalAnd->rightOperand, $visitor),
        ];

        return implode(" {$logicalAnd->token->lexeme} ", $clauses);
    }
}
