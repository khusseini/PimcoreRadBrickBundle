<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle;

use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\Renderer;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase
{
    public function testCanEmit()
    {
        $expected = [
            'first' => 'hello',
            'second' => 'world',
        ];

        $firstArgument = new RenderArgument('type', 'first', $expected['first']);
        $secondArgument = new RenderArgument('type', 'second', $expected['second']);

        $renderer = $this->getInstance();

        $renderer->emitArgument($firstArgument);
        $renderer->emitArgument($secondArgument);

        $generator = $renderer->emit();

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
        $renderer = new Renderer();
        return $renderer;
    }
}
