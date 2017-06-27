<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\Common;

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
    /**
     * Mapping of token domain to Solr field name.
     *
     * @var array
     */
    private $domainFieldMap = [];

    /**
     * Solr field name to be used when no mapping for a domain is found.
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

        $fieldPrefix = $this->getSolrFieldPrefix($token);
        $phraseEscaped = preg_replace("/([\\{$token->quote}])/", '\\\\$1', $token->phrase);

        return "{$fieldPrefix}\"{$phraseEscaped}\"";
    }

    /**
     * Return Solr backend field name prefix for the given $token.
     *
     * @param \QueryTranslator\Languages\Galach\Values\Token\Phrase $token
     *
     * @return string
     */
    private function getSolrFieldPrefix(PhraseToken $token)
    {
        if ($token->domain === '') {
            return '';
        }

        if (isset($this->domainFieldMap[$token->domain])) {
            return $this->domainFieldMap[$token->domain] . ':';
        }

        return $this->defaultFieldName . ':';
    }
}
