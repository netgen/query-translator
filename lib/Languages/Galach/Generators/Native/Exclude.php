<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use QueryTranslator\Languages\Galach\Values\Node\Exclude as ExcludeNode;
use QueryTranslator\Values\Node;
use RuntimeException;

/**
 * Exclude operator Node Visitor implementation.
 */
final class Exclude extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof ExcludeNode;
    }

    public function visit(Node $exclude, Visitor $subVisitor = null)
    {
        if ($subVisitor === null) {
            throw new RuntimeException('Implementation requires sub-visitor');
        }

        /** @var \QueryTranslator\Languages\Galach\Values\Node\Exclude $exclude */
        $clause = $subVisitor->visit($exclude->operand, $subVisitor);

        return "{$exclude->token->lexeme}{$clause}";
    }
}
