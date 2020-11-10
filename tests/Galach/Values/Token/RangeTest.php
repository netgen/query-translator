<?php

namespace QueryTranslator\Tests\Galach\Values\Token;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\Values\Token\Range;

class RangeTest extends TestCase
{
    public function failingTypeDataprovider()
    {
        return [
            ['', 'inclusive'],
            ['', 'exclusive'],
            ['inclusive', ''],
            ['exclusive', ''],
            [null, null],
            ['other', 'inclusive'],
            ['other', 'exclusive'],
            ['inclusive','other'],
            ['exclusive','other'],
            ['inclusive', null],
            ['exclusive', null],
            [null, 'inclusive'],
            [null, 'exclusive'],
        ];
    }

    /**
     * @dataProvider failingTypeDataprovider
     * @param string $type
     */
    public function testConstructorFailsWrongType($startType, $endType)
    {
        $this->expectException(\InvalidArgumentException::class);
        new Range('[a TO b]', 0, '', 'a', 'b', $startType, $endType);
    }
}
