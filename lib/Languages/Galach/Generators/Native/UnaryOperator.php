<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use LogicException;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Node\Exclude;
use QueryTranslator\Languages\Galach\Values\Node\IncludeNode;
use QueryTranslator\Languages\Galach\Values\Node\LogicalNot;
use QueryTranslator\Values\Node;

/**
 * Exclude operator Node Visitor implementation.
 */
final class UnaryOperator extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof IncludeNode || $node instanceof Exclude || $node instanceof LogicalNot;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        if (!$node instanceof IncludeNode && !$node instanceof Exclude && !$node instanceof LogicalNot) {
            throw new LogicException(
                'Visitor implementation accepts instance of Include, Exclude or LogicalNot Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clause = $subVisitor->visit($node->operand, $subVisitor);

        $padding = '';
        if ($node->token->type === Tokenizer::TOKEN_LOGICAL_NOT) {
            $padding = ' ';
        }

        return "{$node->token->lexeme}{$padding}{$clause}";
    }
}
