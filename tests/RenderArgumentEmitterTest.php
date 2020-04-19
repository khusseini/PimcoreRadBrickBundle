<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle;

use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RenderArgumentEmitterTest extends TestCase
{
    use ProphecyTrait;

    public function testCanEmit()
    {
        $expected = [
            'first' => 'hello',
            'second' => 'world',
        ];

        $firstArgument = new RenderArgument('type', 'first', $expected['first']);
        $secondArgument = new RenderArgument('type', 'second', $expected['second']);

        $emitter = $this->getInstance();

        $emitter->emitArgument($firstArgument);
        $emitter->emitArgument($secondArgument);

        $generator = $emitter->emit();

        self::assertInstanceOf(\Generator::class, $generator);
        $renderArguments = iterator_to_array($generator);
        self::assertCount(2, $renderArguments);
        self::assertContainsOnlyInstancesOf(RenderArgument::class, $renderArguments);

        foreach ($renderArguments as $name => $renderArgument) {
            self::assertEquals($expected[$name], $renderArgument->getValue());
            self::assertEquals('type', $renderArgument->getType());
        }
    }

    public function testEmitOnlyOnce()
    {
        $instance = $this->getInstance();
        $argument = new RenderArgument('', 'test');
        $instance->emitArgument($argument);
        $instance->emitArgument($argument);

        $emitted = $instance->emit();
        $emitted = iterator_to_array($emitted);
        self::assertCount(1, $emitted);

        $instance->emitArgument($argument);
        $emitted = $instance->emit();
        $emitted = iterator_to_array($emitted);
        self::assertCount(0, $emitted);
    }

    public function testHas()
    {
        $instance = $this->getInstance();
        $argument = new RenderArgument('', 'test');
        $instance->set($argument);
        self::assertTrue($instance->has($argument->getName()));
    }

    public function testGet()
    {
        $instance = $this->getInstance();
        $argument = new RenderArgument('', 'test');
        $instance->set($argument);
        self::assertEquals($argument, $instance->get($argument->getName()));
    }

    public function getInstance()
    {
        $emitter = new RenderArgumentEmitter();

        return $emitter;
    }

    private function ignore()
    {
        $expected = [
            'first' => 'hello',
            'second' => 'world',
        ];
        $firstArgument = new RenderArgument('type', 'first', $expected['first']);
        $secondArgument = new RenderArgument('type', 'second', $expected['second']);

        $instance = $this->getInstance();
        $instance->emitArgument($firstArgument);
        $instance->emitArgument($secondArgument);

        $renderCallback = function (RenderArgument $renderArgument) use ($expected) {
            $name = $renderArgument->getName();
            self::assertEquals($expected[$name], $renderArgument->getValue());
            self::assertEquals('type', $renderArgument->getType());
        };

        //$instance->render($renderCallback);
    }
}
