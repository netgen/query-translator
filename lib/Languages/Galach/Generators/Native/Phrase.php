<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

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

    public function visit(Node $node, Visitor $visitor = null)
    {
        /** @var \QueryTranslator\Languages\Galach\Values\Node\Term $node */
        /** @var \QueryTranslator\Languages\Galach\Values\Token\Phrase $token */
        $token = $node->token;
        $domainPrefix = empty($token->domain) ? '' : "{$token->domain}:";
        $phraseEscaped = preg_replace("/([\\{$token->quote}])/", '\\\\$1', $token->phrase);

        return "{$domainPrefix}{$token->quote}{$phraseEscaped}{$token->quote}";
    }
}
