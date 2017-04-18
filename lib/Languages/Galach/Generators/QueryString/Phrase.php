<?php

namespace QueryTranslator\Languages\Galach\Generators\QueryString;

use LogicException;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Phrase as PhraseToken;
use QueryTranslator\Values\Node;

/**
 * Phrase Node Visitor implementation.
 */
final class Phrase extends Visitor
{
    /**
     * Mapping of token domain to Elasticsearch field name.
     *
     * @var array
     */
    private $domainFieldMap = [];

    /**
     * Elasticsearch field name to be used when no mapping for a domain is found.
     *
     * @var string
     */
    private $defaultFieldName;

    /**
     * @param array|null $domainFieldMap
     * @param string|null $defaultFieldName
     */
    public function __construct(array $domainFieldMap = null, $defaultFieldName = null)
    {
        if ($domainFieldMap !== null) {
            $this->domainFieldMap = $domainFieldMap;
        }

        $this->defaultFieldName = $defaultFieldName;
    }

    public function accept(Node $node)
    {
        return $node instanceof Term && $node->token instanceof PhraseToken;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        if (!$node instanceof Term) {
            throw new LogicException(
                'Visitor implementation accepts instance of Term Node'
            );
        }

        $token = $node->token;

        if (!$token instanceof PhraseToken) {
            throw new LogicException(
                'Visitor implementation accepts instance of Phrase Token'
            );
        }

        $phraseEscaped = preg_replace("/([\\{$token->quote}])/", '\\\\$1', $token->phrase);
        $fieldName = $this->getElasticsearchField($token);
        $fieldPrefix = $fieldName === null ? '' : "{$fieldName}:";

        return "{$fieldPrefix}\"{$phraseEscaped}\"";
    }

    /**
     * Return Elasticsearch backend field name for the given $token.
     *
     * @param \QueryTranslator\Languages\Galach\Values\Token\Phrase $token
     *
     * @return string|null
     */
    private function getElasticsearchField(PhraseToken $token)
    {
        if ($token->domain === null) {
            return null;
        }

        if (isset($this->domainFieldMap[$token->domain])) {
            return $this->domainFieldMap[$token->domain];
        }

        return $this->defaultFieldName;
    }
}
