<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use LogicException;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Tag as TagToken;
use QueryTranslator\Values\Node;

/**
 * User Node Visitor implementation.
 */
final class Tag extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof Term && $node->token instanceof TagToken;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        if (!$node instanceof Term) {
            throw new LogicException(
                'Visitor implementation accepts instance of Term Node'
            );
        }

        $token = $node->token;

        return "{$token->marker}{$token->tag}";
    }
}
