<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\Common;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\Group as GroupNode;
use QueryTranslator\Languages\Galach\Values\Token\GroupBegin;
use QueryTranslator\Values\Node;

/**
 * Group Node Visitor implementation.
 */
final class Group extends Visitor
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
        return $node instanceof GroupNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof GroupNode) {
            throw new LogicException(
                'Implementation accepts instance of Group Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clauses = [];

        foreach ($node->nodes as $subNode) {
            $clauses[] = $subVisitor->visit($subNode, $subVisitor, $options);
        }

        $fieldPrefix = $this->getSolrFieldPrefix($node->tokenLeft);
        $clauses = implode(' ', $clauses);

        return "{$fieldPrefix}({$clauses})";
    }

    /**
     * Return Solr backend field name prefix for the given $token.
     *
     * @param \QueryTranslator\Languages\Galach\Values\Token\GroupBegin $token
     *
     * @return string
     */
    private function getSolrFieldPrefix(GroupBegin $token)
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
