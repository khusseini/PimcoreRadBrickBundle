<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\ExpressionLanguage;

use Khusseini\PimcoreRadBrickBundle\ExpressionLanguage\ExpressionWrapper;
use PHPUnit\Framework\TestCase;

class ExpressionWrapperTest extends TestCase
{
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
