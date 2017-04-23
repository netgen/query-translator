<?php

namespace QueryTranslator\Languages\Galach\Values\Node;

use QueryTranslator\Values\Node;
use QueryTranslator\Values\Token;

final class Mandatory extends Node
{
    /**
     * @var \QueryTranslator\Values\Node
     */
    public $operand;

    /**
     * @var \QueryTranslator\Values\Token
     */
    public $token;

    /**
     * @param \QueryTranslator\Values\Node $operand
     * @param \QueryTranslator\Values\Token $token
     */
    public function __construct(Node $operand = null, Token $token = null)
    {
        $this->operand = $operand;
        $this->token = $token;
    }

    public function getNodes()
    {
        return [$this->operand];
    }
}
