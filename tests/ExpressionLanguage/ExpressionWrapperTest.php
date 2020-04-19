<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\ExpressionLanguage;

use Khusseini\PimcoreRadBrickBundle\ExpressionLanguage\ExpressionWrapper;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ExpressionWrapperTest extends TestCase
{
    use ProphecyTrait;

    public function testCanProcessExpressions()
    {
        $data = [
            'entry' => ['some_data' => 'static value',],
            'another_entry' => 'entry["some_data"]',
        ];

        $expected = [
            'entry' => ['some_data'=> 'static value',],
            'another_entry' => 'static value',
        ];

        $instance = new ExpressionWrapper();

        $actual = $instance->evaluateExpressions(
            $data,
            ['[another_entry]']
        );

        $this->assertSame($expected, $actual);
    }
}
