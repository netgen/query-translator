<?php

namespace QueryTranslator\Tests\Galach\Tokenizer;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\TokenExtractor;
use QueryTranslator\Languages\Galach\Tokenizer;

/**
 * Text case for TokenExtractor.
 */
class TokenExtractorTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage PCRE regex error code: 2
     */
    public function testExtractThrowsException()
    {
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
}
