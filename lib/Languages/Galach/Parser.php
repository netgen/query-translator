<?php

namespace QueryTranslator\Languages\Galach;

use QueryTranslator\Languages\Galach\Values\Node\Group;
use QueryTranslator\Languages\Galach\Values\Node\LogicalAnd;
use QueryTranslator\Languages\Galach\Values\Node\LogicalNot;
use QueryTranslator\Languages\Galach\Values\Node\LogicalOr;
use QueryTranslator\Languages\Galach\Values\Node\Mandatory;
use QueryTranslator\Languages\Galach\Values\Node\Prohibited;
use QueryTranslator\Languages\Galach\Values\Node\Query;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Parsing;
use QueryTranslator\Values\Correction;
use QueryTranslator\Values\Node;
use QueryTranslator\Values\SyntaxTree;
use QueryTranslator\Values\Token;
use QueryTranslator\Values\TokenSequence;
use SplStack;

/**
 * Galach implementation of the Parsing interface.
 */
final class Parser implements Parsing
{
    /**
     * Parser ignored adjacent unary operator preceding another operator.
     */
    const CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED = 0;

    /**
     * Parser ignored unary operator missing an operand.
     */
    const CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED = 1;

    /**
     * Parser ignored binary operator missing left side operand.
     */
    const CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED = 2;

    /**
     * Parser ignored binary operator missing right side operand.
     */
    const CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED = 3;

    /**
     * Parser ignored binary operator following another operator and connecting operators.
     */
    const CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED = 4;

    /**
     * Parser ignored logical not operators preceding mandatory or prohibited operator.
     */
    const CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED = 5;

    /**
     * Parser ignored empty group and connecting operators.
     */
    const CORRECTION_EMPTY_GROUP_IGNORED = 6;

    /**
     * Parser ignored unmatched left side group delimiter.
     */
    const CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED = 7;

    /**
     * Parser ignored unmatched right side group delimiter.
     */
    const CORRECTION_UNMATCHED_GROUP_RIGHT_DELIMITER_IGNORED = 8;

    /**
     * Parser ignored bailout type token.
     *
     * @see \QueryTranslator\Languages\Galach\Tokenizer::TOKEN_BAILOUT
     */
    const CORRECTION_BAILOUT_TOKEN_IGNORED = 9;

    private static $tokenShortcuts = [
        'operatorNot' => Tokenizer::TOKEN_LOGICAL_NOT | Tokenizer::TOKEN_LOGICAL_NOT_2,
        'operatorPreference' => Tokenizer::TOKEN_MANDATORY | Tokenizer::TOKEN_PROHIBITED,
        'operatorPrefix' => Tokenizer::TOKEN_MANDATORY | Tokenizer::TOKEN_PROHIBITED | Tokenizer::TOKEN_LOGICAL_NOT_2,
        'operatorUnary' => Tokenizer::TOKEN_MANDATORY | Tokenizer::TOKEN_PROHIBITED | Tokenizer::TOKEN_LOGICAL_NOT | Tokenizer::TOKEN_LOGICAL_NOT_2,
        'operatorBinary' => Tokenizer::TOKEN_LOGICAL_AND | Tokenizer::TOKEN_LOGICAL_OR,
        'operator' => Tokenizer::TOKEN_LOGICAL_AND | Tokenizer::TOKEN_LOGICAL_OR | Tokenizer::TOKEN_MANDATORY | Tokenizer::TOKEN_PROHIBITED | Tokenizer::TOKEN_LOGICAL_NOT | Tokenizer::TOKEN_LOGICAL_NOT_2,
        'groupDelimiter' => Tokenizer::TOKEN_GROUP_BEGIN | Tokenizer::TOKEN_GROUP_END,
        'binaryOperatorAndWhitespace' => Tokenizer::TOKEN_LOGICAL_AND | Tokenizer::TOKEN_LOGICAL_OR | Tokenizer::TOKEN_WHITESPACE,
    ];

    private static $shifts = [
        Tokenizer::TOKEN_WHITESPACE => 'shiftWhitespace',
        Tokenizer::TOKEN_TERM => 'shiftTerm',
        Tokenizer::TOKEN_GROUP_BEGIN => 'shiftGroupBegin',
        Tokenizer::TOKEN_GROUP_END => 'shiftGroupEnd',
        Tokenizer::TOKEN_LOGICAL_AND => 'shiftBinaryOperator',
        Tokenizer::TOKEN_LOGICAL_OR => 'shiftBinaryOperator',
        Tokenizer::TOKEN_LOGICAL_NOT => 'shiftLogicalNot',
        Tokenizer::TOKEN_LOGICAL_NOT_2 => 'shiftLogicalNot2',
        Tokenizer::TOKEN_MANDATORY => 'shiftPreference',
        Tokenizer::TOKEN_PROHIBITED => 'shiftPreference',
        Tokenizer::TOKEN_BAILOUT => 'shiftBailout',
    ];

    private static $nodeToReductionGroup = [
        Group::class => 'group',
        LogicalAnd::class => 'logicalAnd',
        LogicalOr::class => 'logicalOr',
        LogicalNot::class => 'unaryOperator',
        Mandatory::class => 'unaryOperator',
        Prohibited::class => 'unaryOperator',
        Term::class => 'term',
    ];

    private static $reductionGroups = [
        'group' => [
            'reduceGroup',
            'reducePreference',
            'reduceLogicalNot',
            'reduceLogicalAnd',
            'reduceLogicalOr',
        ],
        'unaryOperator' => [
            'reduceLogicalNot',
            'reduceLogicalAnd',
            'reduceLogicalOr',
        ],
        'logicalOr' => [],
        'logicalAnd' => [
            'reduceLogicalOr',
        ],
        'term' => [
            'reducePreference',
            'reduceLogicalNot',
            'reduceLogicalAnd',
            'reduceLogicalOr',
        ],
    ];

    /**
     * Input tokens.
     *
     * @var \QueryTranslator\Values\Token[]
     */
    private $tokens;

    /**
     * Query stack.
     *
     * @var \SplStack
     */
    private $stack;

    /**
     * An array of applied corrections.
     *
     * @var \QueryTranslator\Values\Correction[]
     */
    private $corrections = [];

    public function parse(TokenSequence $tokenSequence)
    {
        $this->init($tokenSequence->tokens);

        while (!empty($this->tokens)) {
            $node = $this->shift();

            if ($node instanceof Node) {
                $this->reduce($node);
            }
        }

        $this->reduceQuery();

        return new SyntaxTree($this->stack->top(), $tokenSequence, $this->corrections);
    }

    private function shift()
    {
        $token = array_shift($this->tokens);
        $shift = self::$shifts[$token->type];

        return $this->{$shift}($token);
    }

    private function reduce(Node $node)
    {
        $previousNode = null;
        $reductionIndex = null;

        while ($node instanceof Node) {
            // Reset reduction index on first iteration or on Node change
            if ($node !== $previousNode) {
                $reductionIndex = 0;
            }

            // If there are no reductions to try, put the Node on the stack
            // and continue shifting
            $reduction = $this->getReduction($node, $reductionIndex);
            if ($reduction === null) {
                $this->stack->push($node);
                break;
            }

            $previousNode = $node;
            $node = $this->{$reduction}($node);
            ++$reductionIndex;
        }
    }

    protected function shiftWhitespace()
    {
        if ($this->isTopStackToken(self::$tokenShortcuts['operatorPrefix'])) {
            $this->addCorrection(
                self::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED,
                $this->stack->pop()
            );
        }
    }

    protected function shiftPreference(Token $token)
    {
        return $this->shiftAdjacentUnaryOperator($token, self::$tokenShortcuts['operator']);
    }

    protected function shiftAdjacentUnaryOperator(Token $token, $tokenMask)
    {
        if ($this->isToken(reset($this->tokens), $tokenMask)) {
            $this->addCorrection(
                self::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED,
                $token
            );

            return null;
        }

        $this->stack->push($token);
    }

    protected function shiftLogicalNot(Token $token)
    {
        $this->stack->push($token);
    }

    protected function shiftLogicalNot2(Token $token)
    {
        $tokenMask = self::$tokenShortcuts['operator'] & ~Tokenizer::TOKEN_LOGICAL_NOT_2;

        return $this->shiftAdjacentUnaryOperator($token, $tokenMask);
    }

    protected function shiftBinaryOperator(Token $token)
    {
        if ($this->stack->isEmpty() || $this->isTopStackToken(Tokenizer::TOKEN_GROUP_BEGIN)) {
            $this->addCorrection(
                self::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED,
                $token
            );

            return null;
        }

        if ($this->isTopStackToken(self::$tokenShortcuts['operator'])) {
            $this->ignoreBinaryOperatorFollowingOperator($token);

            return null;
        }

        $this->stack->push($token);
    }

    private function ignoreBinaryOperatorFollowingOperator(Token $token)
    {
        $precedingOperators = $this->ignorePrecedingOperators(self::$tokenShortcuts['operator']);
        $followingOperators = $this->ignoreFollowingOperators();

        $this->addCorrection(
            self::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED,
            ...array_merge(
                $precedingOperators,
                [$token],
                $followingOperators
            )
        );
    }

    protected function shiftTerm(Token $token)
    {
        return new Term($token);
    }

    protected function shiftGroupBegin(Token $token)
    {
        $this->stack->push($token);
    }

    protected function shiftGroupEnd(Token $token)
    {
        $this->stack->push($token);

        return new Group();
    }

    protected function shiftBailout(Token $token)
    {
        $this->addCorrection(self::CORRECTION_BAILOUT_TOKEN_IGNORED, $token);
    }

    protected function reducePreference(Node $node)
    {
        if (!$this->isTopStackToken(self::$tokenShortcuts['operatorPreference'])) {
            return $node;
        }

        $token = $this->stack->pop();

        if ($this->isToken($token, Tokenizer::TOKEN_MANDATORY)) {
            return new Mandatory($node, $token);
        }

        return new Prohibited($node, $token);
    }

    protected function reduceLogicalNot(Node $node)
    {
        if (!$this->isTopStackToken(self::$tokenShortcuts['operatorNot'])) {
            return $node;
        }

        if ($node instanceof Mandatory || $node instanceof Prohibited) {
            $this->ignoreLogicalNotOperatorsPrecedingPreferenceOperator();

            return $node;
        }

        return new LogicalNot($node, $this->stack->pop());
    }

    public function ignoreLogicalNotOperatorsPrecedingPreferenceOperator()
    {
        $precedingOperators = $this->ignorePrecedingOperators(self::$tokenShortcuts['operatorNot']);

        if (!empty($precedingOperators)) {
            $this->addCorrection(
                self::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED,
                ...$precedingOperators
            );
        }
    }

    protected function reduceLogicalAnd(Node $node)
    {
        if ($this->stack->count() <= 1 || !$this->isTopStackToken(Tokenizer::TOKEN_LOGICAL_AND)) {
            return $node;
        }

        $token = $this->stack->pop();
        $leftOperand = $this->stack->pop();

        return new LogicalAnd($leftOperand, $node, $token);
    }

    /**
     * Reduce logical OR.
     *
     * @param \QueryTranslator\Values\Node $node
     * @param bool $inGroup Reduce inside a group
     *
     * @return null|\QueryTranslator\Languages\Galach\Values\Node\LogicalOr|\QueryTranslator\Values\Node
     */
    protected function reduceLogicalOr(Node $node, $inGroup = false)
    {
        if ($this->stack->count() <= 1 || !$this->isTopStackToken(Tokenizer::TOKEN_LOGICAL_OR)) {
            return $node;
        }

        // If inside a group don't look for following logical AND
        if (!$inGroup) {
            $this->popWhitespace();
            // If the next token is logical AND, put the node on stack
            // as that has precedence over logical OR
            if ($this->isToken(reset($this->tokens), Tokenizer::TOKEN_LOGICAL_AND)) {
                $this->stack->push($node);

                return null;
            }
        }

        $token = $this->stack->pop();
        $leftOperand = $this->stack->pop();

        return new LogicalOr($leftOperand, $node, $token);
    }

    protected function reduceGroup(Group $group)
    {
        $rightDelimiter = $this->stack->pop();

        // Pop dangling tokens
        $this->popTokens(~Tokenizer::TOKEN_GROUP_BEGIN);

        if ($this->isTopStackToken(Tokenizer::TOKEN_GROUP_BEGIN)) {
            $leftDelimiter = $this->stack->pop();
            $this->ignoreEmptyGroup($leftDelimiter, $rightDelimiter);
            $this->reduceRemainingLogicalOr(true);

            return null;
        }

        $this->reduceRemainingLogicalOr(true);

        $group->nodes = $this->collectTopStackNodes();
        $group->tokenLeft = $this->stack->pop();
        $group->tokenRight = $rightDelimiter;

        return $group;
    }

    /**
     * Collect all Nodes from the top of the stack.
     *
     * @return \QueryTranslator\Values\Node[]
     */
    private function collectTopStackNodes()
    {
        $nodes = [];

        while (!$this->stack->isEmpty() && $this->stack->top() instanceof Node) {
            array_unshift($nodes, $this->stack->pop());
        }

        return $nodes;
    }

    private function ignoreEmptyGroup(Token $leftDelimiter, Token $rightDelimiter)
    {
        $precedingOperators = $this->ignorePrecedingOperators(self::$tokenShortcuts['operator']);
        $followingOperators = $this->ignoreFollowingOperators();

        $this->addCorrection(
            self::CORRECTION_EMPTY_GROUP_IGNORED,
            ...array_merge(
                $precedingOperators,
                [$leftDelimiter, $rightDelimiter],
                $followingOperators
            )
        );
    }

    /**
     * Initialize the parser with given array of $tokens.
     *
     * @param \QueryTranslator\Values\Token[] $tokens
     */
    private function init(array $tokens)
    {
        $this->corrections = [];
        $this->tokens = $tokens;
        $this->cleanupGroupDelimiters($this->tokens);
        $this->stack = new SplStack();
    }

    private function getReduction(Node $node, $reductionIndex)
    {
        $reductionGroup = self::$nodeToReductionGroup[get_class($node)];

        if (isset(self::$reductionGroups[$reductionGroup][$reductionIndex])) {
            return self::$reductionGroups[$reductionGroup][$reductionIndex];
        }

        return null;
    }

    private function reduceQuery()
    {
        $this->popTokens();
        $this->reduceRemainingLogicalOr();
        $nodes = [];

        while (!$this->stack->isEmpty()) {
            array_unshift($nodes, $this->stack->pop());
        }

        $this->stack->push(new Query($nodes));
    }

    /**
     * Check if the given $token is an instance of Token.
     *
     * Optionally also checks given Token $typeMask.
     *
     * @param mixed $token
     * @param int $typeMask
     *
     * @return bool
     */
    private function isToken($token, $typeMask = null)
    {
        if (!$token instanceof Token) {
            return false;
        }

        if (null === $typeMask || $token->type & $typeMask) {
            return true;
        }

        return false;
    }

    private function isTopStackToken($type = null)
    {
        return !$this->stack->isEmpty() && $this->isToken($this->stack->top(), $type);
    }

    /**
     * Remove whitespace Tokens from the beginning of the token array.
     */
    private function popWhitespace()
    {
        while ($this->isToken(reset($this->tokens), Tokenizer::TOKEN_WHITESPACE)) {
            array_shift($this->tokens);
        }
    }

    /**
     * Remove all Tokens from the top of the query stack and log Corrections as necessary.
     *
     * Optionally also checks that Token matches given $typeMask.
     *
     * @param int $typeMask
     */
    private function popTokens($typeMask = null)
    {
        while ($this->isTopStackToken($typeMask)) {
            $token = $this->stack->pop();
            if ($token->type & self::$tokenShortcuts['operatorUnary']) {
                $this->addCorrection(
                    self::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED,
                    $token
                );
            } else {
                $this->addCorrection(
                    self::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED,
                    $token
                );
            }
        }
    }

    private function ignorePrecedingOperators($type)
    {
        $tokens = [];
        while ($this->isTopStackToken($type)) {
            array_unshift($tokens, $this->stack->pop());
        }

        return $tokens;
    }

    private function ignoreFollowingOperators()
    {
        $tokenMask = self::$tokenShortcuts['binaryOperatorAndWhitespace'];
        $tokens = [];
        while ($this->isToken(reset($this->tokens), $tokenMask)) {
            $token = array_shift($this->tokens);
            if ($token->type & self::$tokenShortcuts['operatorBinary']) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * Reduce logical OR possibly remaining after reaching end of group or query.
     *
     * @param bool $inGroup Reduce inside a group
     */
    private function reduceRemainingLogicalOr($inGroup = false)
    {
        if (!$this->stack->isEmpty() && !$this->isTopStackToken()) {
            $node = $this->reduceLogicalOr($this->stack->pop(), $inGroup);
            $this->stack->push($node);
        }
    }

    /**
     * Clean up group delimiter tokens, removing unmatched left and right delimiter.
     *
     * Closest group delimiters will be matched first, unmatched remainder is removed.
     *
     * @param \QueryTranslator\Values\Token[] $tokens
     */
    private function cleanupGroupDelimiters(array &$tokens)
    {
        $indexes = $this->getUnmatchedGroupDelimiterIndexes($tokens);

        while (!empty($indexes)) {
            $lastIndex = array_pop($indexes);
            $token = $tokens[$lastIndex];
            unset($tokens[$lastIndex]);

            if ($token->type === Tokenizer::TOKEN_GROUP_BEGIN) {
                $this->addCorrection(
                    self::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED,
                    $token
                );
            } else {
                $this->addCorrection(
                    self::CORRECTION_UNMATCHED_GROUP_RIGHT_DELIMITER_IGNORED,
                    $token
                );
            }
        }
    }

    private function getUnmatchedGroupDelimiterIndexes(array &$tokens)
    {
        $trackLeft = [];
        $trackRight = [];

        foreach ($tokens as $index => $token) {
            if (!$this->isToken($token, self::$tokenShortcuts['groupDelimiter'])) {
                continue;
            }

            if ($this->isToken($token, Tokenizer::TOKEN_GROUP_BEGIN)) {
                $trackLeft[] = $index;
                continue;
            }

            if (empty($trackLeft)) {
                $trackRight[] = $index;
            } else {
                array_pop($trackLeft);
            }
        }

        return array_merge($trackLeft, $trackRight);
    }

    private function addCorrection($type, Token ...$tokens)
    {
        $this->corrections[] = new Correction($type, ...$tokens);
    }
}
