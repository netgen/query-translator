<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

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

    public function visit(Node $node, Visitor $visitor = null)
    {
        /** @var \QueryTranslator\Languages\Galach\Values\Node\Term $node */
        /** @var \QueryTranslator\Languages\Galach\Values\Token\Tag $token */
        $token = $node->token;

        return "{$token->marker}{$token->tag}";
    }
}
