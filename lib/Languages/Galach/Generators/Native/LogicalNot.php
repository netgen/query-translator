<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use QueryTranslator\Languages\Galach\Values\Node\LogicalNot as LogicalNotNode;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Values\Node;

/**
 * LogicalNot operator Node Visitor implementation.
 */
final class LogicalNot extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof LogicalNotNode;
    }

    public function visit(Node $logicalNot, Visitor $visitor = null)
    {
        /** @var \QueryTranslator\Languages\Galach\Values\Node\LogicalNot $logicalNot */
        $clause = $visitor->visit($logicalNot->operand, $visitor);

        $padding = '';
        if ($logicalNot->token->type === Tokenizer::TOKEN_LOGICAL_NOT) {
            $padding = ' ';
        }

        return "{$logicalNot->token->lexeme}{$padding}{$clause}";
    }
}
