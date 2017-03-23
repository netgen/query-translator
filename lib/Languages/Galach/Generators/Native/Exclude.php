<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use QueryTranslator\Languages\Galach\Values\Node\Exclude as ExcludeNode;
use QueryTranslator\Values\Node;

/**
 * Exclude operator Node Visitor implementation.
 */
final class Exclude extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof ExcludeNode;
    }

    public function visit(Node $exclude, Visitor $visitor = null)
    {
        /** @var \QueryTranslator\Languages\Galach\Values\Node\Exclude $exclude */
        $clause = $visitor->visit($exclude->operand, $visitor);

        return "{$exclude->token->lexeme}{$clause}";
    }
}
