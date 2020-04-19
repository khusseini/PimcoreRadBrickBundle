<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use InvalidArgumentException;
use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\ConfiguratorData;
use Khusseini\PimcoreRadBrickBundle\Configurator\GroupConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupConfiguratorTest extends BaseConfiguratorTestCase
{
    public function getInstance(string $case): AbstractConfigurator
    {
        return new GroupConfigurator();
    }

    public function getSupportsEditableCases(): array
    {
        return [
            [
                'positive',
                'group: boxes',
                function (bool $actual) {
                    self::assertTrue($actual);
                },
            ], [
                'negative',
                'type: input',
                function (bool $acutal) {
                    self::assertFalse($acutal);
                },
            ],
        ];
    }

    public function getPreCreateEditablesData(): array
    {
        $successCase = <<<YAML
areabricks:
  test:
    groups:
      boxes:
        prop: value
    editables:
      test:
        group: boxes
YAML;
        $skipCase = <<<YAML
areabricks:
  test:
    groups:
      boxes:
        prop: value
    editables:
      test:
        type: input
YAML;
        $exceptionCase = <<<YAML
areabricks:
  test:
    editables:
      test:
        group: invalid
        type: input
YAML;

        return [
            [
                'successCase',
                $successCase,
                'test',
                function (ConfiguratorData $data) {
                    $config = $data->getConfig();
                    $brick = $config['areabricks']['test'];
                    $editable = $brick['editables']['test'];
                    $this->assertArrayHasKey('prop', $editable);
                },
            ], [
                'skipCase',
                $skipCase,
                'test',
                function (ConfiguratorData $data) {
                    $config = $data->getConfig();
                    $brick = $config['areabricks']['test'];
                    $editable = $brick['editables']['test'];
                    $this->assertArrayNotHasKey('prop', $editable);
                },
            ], [
                'exceptionCase',
                $exceptionCase,
                'test',
                function () {
                },
                InvalidArgumentException::class,
            ],
        ];
    }

    public function getDoCreateEditablesData(): array
    {
        return [['skip', '', '', function () {
        }, '', true]];
    }

    public function getPostCreateEditablesData(): array
    {
        $singleEditable = <<<YAML
areabricks:
  test:
    groups:
      boxes:
        prop: value
    editables:
      test:
        group: boxes
        prop: value
YAML;
        $noEditable = <<<YAML
areabricks:
  test:
    editables:
      test:
        group: boxes
        prop: value
YAML;
        $skipEditable = <<<YAML
areabricks:
  test:
    groups:
      boxes:
        prop: value
    editables:
      test:
        group: boxes
        prop: value
      skipme:
        prop: value
YAML;
        $multipleEditables = <<<YAML
areabricks:
  test:
    groups:
      boxes:
        prop: value
    editables:
      test:
        group: boxes
        prop: value
YAML;

        return [
            [
                'single_editable',
                $singleEditable,
                'test',
                function (RenderArgumentEmitter $emitter) {
                    $renderArguments = iterator_to_array($emitter->emit());
                    self::assertCount(1, $renderArguments);
                    self::assertArrayHasKey('boxes', $renderArguments);

                    $boxes = $renderArguments['boxes'];
                    self::assertEquals('collection', $boxes->getType());
                    self::assertCount(1, $boxes->getValue());

                    $boxes = $boxes->getValue();
                    $box = $boxes[0];
                    $boxValue = $box->getValue();
                    self::assertEquals('collection', $box->getType());
                    self::assertCount(1, $boxValue);
                    self::assertArrayHasKey('test', $boxValue);
                },
            ], [
                'no_editable',
                $noEditable,
                'test',
                function (RenderArgumentEmitter $emitter) {
                    $renderArguments = iterator_to_array($emitter->emit());
                    self::assertCount(0, $renderArguments);
                },
            ], [
                'skip_editables',
                $skipEditable,
                'test',
                function (RenderArgumentEmitter $emitter) {
                    $renderArguments = iterator_to_array($emitter->emit());
                    self::assertCount(1, $renderArguments);
                },
            ], [
                'multiple_editables',
                $multipleEditables,
                'test',
                function (RenderArgumentEmitter $emitter) {
                    $renderArguments = iterator_to_array($emitter->emit());
                    self::assertCount(2, $renderArguments);
                    self::assertArrayHasKey('boxes', $renderArguments);

                    $boxes = $renderArguments['boxes'];
                    self::assertEquals('collection', $boxes->getType());
                    self::assertCount(5, $boxes->getValue());

                    $boxesValue = $boxes->getValue();
                    self::assertContainsOnlyInstancesOf(RenderArgument::class, $boxesValue);

                    foreach ($boxesValue as $name => $argument) {
                        self::assertEquals('collection', $argument->getType());
                        self::assertCount(1, $argument->getValue());
                        self::assertArrayHasKey('test', $argument->getValue());

                        $testEditable = $argument->getValue()['test'];
                        self::assertEquals('test_'.$name, $testEditable->getValue());
                        self::assertEquals('reference', $testEditable->getType());
                    }
                },
            ],
        ];
    }

    protected function setPostCreateEditablesArguments(string $case, RenderArgumentEmitter $emitter): void
    {
        if ('multiple_editables' !== $case) {
            return;
        }

        $items = [];
        for ($i = 0; $i < 5; ++$i) {
            $config = ['group' => 'boxes', 'prop' => 'value'];
            $items[] = new RenderArgument('editable', (string) $i, $config);
        }

        $collection = new RenderArgument('collection', 'test', $items);
        $emitter->emitArgument($collection);
    }

    public function testConfigureEditableOptions()
    {
        $instance = $this->getInstance('');
        $or = new OptionsResolver();
        $instance->configureEditableOptions($or);
        self::assertTrue($or->isDefined('group'));
    }
}
