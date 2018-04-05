<?php

namespace QueryTranslator\Tests\Galach\Values\Token;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\Values\Token\Range;

class RangeTest extends TestCase
{
    public function failingStartSymbolDataprovider()
    {
        return [
            [''],
            ['/'],
            ['('],
        ];
    }

    /**
     * @dataProvider failingStartSymbolDataprovider
     * @param string $startSymbol
     */
    public function testGetTypeByStartFails($startSymbol)
    {
        $this->expectException(\InvalidArgumentException::class);
        Range::getTypeByStart($startSymbol);
    }

    public function successfulStartSymbolDataprovider()
    {
        return [
            ['inclusive', '['],
            ['exclusive', '{'],
        ];
    }

    /**
     * @dataProvider successfulStartSymbolDataprovider
     * @param string $expectedType
     * @param string $startSymbol
     */
    public function testGetTypeByStartSucceeds($expectedType, $startSymbol)
    {
        $this->assertSame($expectedType, Range::getTypeByStart($startSymbol));
    }

    public function failingTypeDataprovider()
    {
        return [
            [''],
            [null],
            ['other'],
        ];
    }

    /**
     * @dataProvider failingTypeDataprovider
     * @param string $type
     */
    public function testConstructorFailsWrongType($type)
    {
        $this->expectException(\InvalidArgumentException::class);
        new Range('[a TO b]', 0, '', 'a', 'b', $type);
    }
}
