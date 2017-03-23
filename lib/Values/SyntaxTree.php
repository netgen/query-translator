<?php

namespace QueryTranslator\Values;

/**
 * Syntax tree is an abstract hierarchical representation of the query syntax,
 * intended for easy conversion into different concrete formats.
 *
 * @see \QueryTranslator\Parsing::parse()
 */
class SyntaxTree
{
    /**
     * The root node of the syntax tree.
     *
     * @var \QueryTranslator\Values\Node
     */
    public $rootNode;

    /**
     * Token sequence that was parsed into this syntax tree.
     *
     * @var \QueryTranslator\Values\TokenSequence
     */
    public $tokenSequence;

    /**
     * An array of corrections performed while parsing the token sequence.
     *
     * @var \QueryTranslator\Values\Correction[]
     */
    public $corrections;

    /**
     * @param \QueryTranslator\Values\Node $rootNode
     * @param \QueryTranslator\Values\TokenSequence $tokenSequence
     * @param \QueryTranslator\Values\Correction[] $corrections
     */
    public function __construct(Node $rootNode, TokenSequence $tokenSequence, array $corrections)
    {
        $this->rootNode = $rootNode;
        $this->tokenSequence = $tokenSequence;
        $this->corrections = $corrections;
    }
}
