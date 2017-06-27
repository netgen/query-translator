<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Phrase as PhraseToken;
use QueryTranslator\Values\Node;

/**
 * Phrase Node Visitor implementation.
 */
final class Phrase extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof Term && $node->token instanceof PhraseToken;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof Term) {
            throw new LogicException(
                'Implementation accepts instance of Term Node'
            );
        }

        $token = $node->token;

        if (!$token instanceof PhraseToken) {
            throw new LogicException(
                'Implementation accepts instance of Phrase Token'
            );
        }

        $domainPrefix = $token->domain === '' ? '' : "{$token->domain}:";
        $phraseEscaped = preg_replace("/([\\{$token->quote}])/", '\\\\$1', $token->phrase);

        return "{$domainPrefix}{$token->quote}{$phraseEscaped}{$token->quote}";
    }
}
