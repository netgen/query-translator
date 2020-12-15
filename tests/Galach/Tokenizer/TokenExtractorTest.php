<?php

namespace QueryTranslator\Tests\Galach\Tokenizer;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\TokenExtractor;
use QueryTranslator\Languages\Galach\TokenExtractor\Full;
use QueryTranslator\Languages\Galach\TokenExtractor\Text;
use QueryTranslator\Languages\Galach\Tokenizer;
use RuntimeException;

/**
 * Text case for TokenExtractor.
 */
class TokenExtractorTest extends TestCase
{
    public function testExtractThrowsExceptionPCRE()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PCRE regex error code: 2');

        /** @var \QueryTranslator\Languages\Galach\TokenExtractor|\PHPUnit_Framework_MockObject_MockObject $extractor */
        $extractor = $this->getMockBuilder(TokenExtractor::class)
            ->setMethods(['getExpressionTypeMap'])
            ->getMockForAbstractClass();

        $extractor->expects($this->once())
            ->method('getExpressionTypeMap')
            ->willReturn(
                [
                    '/(?:\D+|<\d+>)*[!?]/' => Tokenizer::TOKEN_WHITESPACE,
                ]
            );

        $extractor->extract('foobar foobar foobar', 0);
    }

    public function testFullExtractTermTokenThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not extract term token from the given data');

        $extractor = new Full();
        $reflectedClass = new \ReflectionClass($extractor);
        $reflectedProperty = $reflectedClass->getProperty('expressionTypeMap');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue(
            [
                '/(?<lexeme>foobar)/' => Tokenizer::TOKEN_TERM,
            ]
        );

        $extractor->extract('foobar', 0);
    }

    public function testTextExtractTermTokenThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not extract term token from the given data');

        $extractor = new Text();
        $reflectedClass = new \ReflectionClass($extractor);
        $reflectedProperty = $reflectedClass->getProperty('expressionTypeMap');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue(
            [
                '/(?<lexeme>foobar)/' => Tokenizer::TOKEN_TERM,
            ]
        );

        $extractor->extract('foobar', 0);
    }
}
