<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use LogicException;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Word as WordToken;
use QueryTranslator\Values\Node;

/**
 * Word Node Visitor implementation.
 */
final class Word extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof Term && $node->token instanceof WordToken;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        if (!$node instanceof Term) {
            throw new LogicException(
                'Visitor implementation accepts instance of Term Node'
            );
        }

        $token = $node->token;
        $domainPrefix = empty($token->domain) ? '' : "{$token->domain}:";
        $wordEscaped = preg_replace('/([\\\'"+\-!():#@ ])/', '\\\\$1', $token->word);

        return "{$domainPrefix}{$wordEscaped}";
    }
}
