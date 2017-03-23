<?php

namespace QueryTranslator\Languages\Galach\Values\Node;

use QueryTranslator\Values\Node;
use QueryTranslator\Values\Token;

final class LogicalAnd extends Node
{
    /**
     * @var \QueryTranslator\Values\Node
     */
    public $leftOperand;

    /**
     * @var \QueryTranslator\Values\Node
     */
    public $rightOperand;

    /**
     * @var \QueryTranslator\Values\Token
     */
    public $token;

    /**
     * @param \QueryTranslator\Values\Node $leftOperand
     * @param \QueryTranslator\Values\Node $rightOperand
     * @param \QueryTranslator\Values\Token $token
     */
    public function __construct(
        Node $leftOperand = null,
        Node $rightOperand = null,
        Token $token = null
    ) {
        $this->leftOperand = $leftOperand;
        $this->rightOperand = $rightOperand;
        $this->token = $token;
    }

    public function getNodes()
    {
        return [
            $this->leftOperand,
            $this->rightOperand,
        ];
    }
}
