<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

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

    public function visit(Node $logicalOr, Visitor $visitor = null)
    {
        /** @var \QueryTranslator\Languages\Galach\Values\Node\LogicalOr $logicalOr */
        $clauses = [
            $visitor->visit($logicalOr->leftOperand, $visitor),
            $visitor->visit($logicalOr->rightOperand, $visitor),
        ];

        return implode(" {$logicalOr->token->lexeme} ", $clauses);
    }
}
