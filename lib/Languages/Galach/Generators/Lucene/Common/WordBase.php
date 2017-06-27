<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\Common;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Word as WordToken;
use QueryTranslator\Values\Node;

/**
 * Base Word Node Visitor implementation.
 */
abstract class WordBase extends Visitor
{
    /**
     * Mapping of token domain to the backend field name.
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
        return $node instanceof Term && $node->token instanceof WordToken;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof Term) {
            throw new LogicException(
                'Implementation accepts instance of Term Node'
            );
        }

        $token = $node->token;

        if (!$token instanceof WordToken) {
            throw new LogicException(
                'Implementation accepts instance of Word Token'
            );
        }

        $fieldPrefix = $this->getSolrFieldPrefix($token);
        $wordEscaped = $this->escapeWord($token->word);

        return "{$fieldPrefix}{$wordEscaped}";
    }

    /**
     * Escape special characters in the given word $string.
     *
     * @param string $string
     *
     * @return string
     */
    abstract protected function escapeWord($string);

    /**
     * Return backend field name prefix for the given $token.
     *
     * @param \QueryTranslator\Languages\Galach\Values\Token\Word $token
     *
     * @return string
     */
    private function getSolrFieldPrefix(WordToken $token)
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
