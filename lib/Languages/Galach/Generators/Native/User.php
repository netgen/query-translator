<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\User as UserToken;
use QueryTranslator\Values\Node;

/**
 * User Node Visitor implementation.
 */
final class User extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof Term && $node->token instanceof UserToken;
    }

    public function visit(Node $node, Visitor $visitor = null)
    {
        /** @var \QueryTranslator\Languages\Galach\Values\Node\Term $node */
        /** @var \QueryTranslator\Languages\Galach\Values\Token\User $token */
        $token = $node->token;

        return "{$token->marker}{$token->user}";
    }
}
