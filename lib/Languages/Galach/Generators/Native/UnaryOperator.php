<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Node\LogicalNot;
use QueryTranslator\Languages\Galach\Values\Node\Mandatory;
use QueryTranslator\Languages\Galach\Values\Node\Prohibited;
use QueryTranslator\Values\Node;

/**
 * Unary operator Node Visitor implementation.
 */
final class UnaryOperator extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof Mandatory || $node instanceof Prohibited || $node instanceof LogicalNot;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof Mandatory && !$node instanceof Prohibited && !$node instanceof LogicalNot) {
            throw new LogicException(
                'Implementation accepts instance of Mandatory, Prohibited or LogicalNot Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clause = $subVisitor->visit($node->operand, $subVisitor, $options);

        $padding = '';
        if ($node->token->type === Tokenizer::TOKEN_LOGICAL_NOT) {
            $padding = ' ';
        }

        return "{$node->token->lexeme}{$padding}{$clause}";
    }
}
