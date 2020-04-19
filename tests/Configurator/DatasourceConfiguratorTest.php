<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\ConfiguratorData;
use Khusseini\PimcoreRadBrickBundle\Configurator\DatasourceConfigurator;
use Khusseini\PimcoreRadBrickBundle\ContextInterface;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use Prophecy\Argument;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tests\Khusseini\PimcoreRadBrickBundle\AbstractTestCase;

class DatasourceConfiguratorTest extends AbstractTestCase
{
    public function canPreCreateData()
    {
        $simpleConfig = <<<YAML
datasources:
  main_source:
    service_id: service_object
    method: getData
    args:
    - first
    - second
YAML;
        $recursiveConfig = <<<YAML
datasources:
  main_source:
    service_id: service_object
    method: getData
    args:
    - sub:
        first: first
        second: second
YAML;
        return [
            [
                $simpleConfig,
                function (string $first, string $second) {
                    $this->assertEquals('hello', $first);
                    $this->assertEquals('world', $second);
                }
            ], [
                $recursiveConfig,
                function (array $first) {
                    $this->assertArrayHasKey('sub', $first);
                    $sub = $first['sub'];
                    $this->assertArrayHasKey('first', $sub);
                    $this->assertArrayHasKey('second', $sub);
                    $this->assertEquals('hello', $sub['first']);
                    $this->assertEquals('world', $sub['second']);
                }
            ]
        ];
    }


    /**
     * @dataProvider canPreCreateData
     */
    public function testCanPreCreate(string $yaml, callable $assert)
    {
        $config = $this->parseYaml($yaml);

        $service = new class($assert) {
            private $tester;
            public function __construct($tester)
            {
                $this->tester = $tester;
            }
            public function getData(...$args): array
            {
                $tester = $this->tester;
                $tester(...$args);
                return  [0, 1, 2, 3];
            }
        };

        foreach ($config['datasources'] as $name => $dsconfig) {
            if ($dsconfig['service_id'] === 'service_object') {
                $dsconfig['service_id'] = $service;
                $config['datasources'][$name] = $dsconfig;
            }
        }

        $areabricks = <<<YAML
areabricks:
  testbrick:
    datasources:
      testsource:
        id: main_source
        args:
          first: hello
          second: world
YAML;

        $areabricks = $this->parseYaml($areabricks);
        $config['areabricks'] = $areabricks['areabricks'];

        $instance = new DatasourceConfigurator();
        $container = new \ArrayObject();
        $context = $this->prophesize(ContextInterface::class);
        $context->toArray()->willReturn([]);
        $context->setDatasources(Argument::any())
            ->will(
                function ($args) use ($container) {
                    $container['datasources'] = $args[0];
                }
            );

        $data = new ConfiguratorData($context->reveal());
        $data->setConfig($config);
        $instance->preCreateEditables('testbrick', $data);
        $context = $data->getContext();

        self::assertTrue(isset($container['datasources']));
        self::assertInstanceOf(DatasourceRegistry::class, $container['datasources']);
    }


    public function canGenerateDatasourcesData()
    {
        $simpleconfig = <<<YAML
datasources:
  main_source:
    service_id: service_object
    method: getData
    args:
    - first
    - second
areabricks:
  testbrick:
    datasources:
      testsource:
        id: main_source
        args:
          first: hello
          second: world
YAML;
        $recursiveconfig = <<<YAML
datasources:
  main_source:
    service_id: service_object
    method: getData
    args:
    - sub:
        first: first
        second: second
areabricks:
  testbrick:
    datasources:
      testsource:
        id: main_source
        args:
          first: hello
          second:
            this:
              goes: dummy
YAML;
        return [
            [$simpleconfig],
            [$recursiveconfig]
        ];
    }

    /**
     * @dataProvider canGenerateDatasourcesData
     */
    public function testCanGenerateDatasources(string $config)
    {
        $service = new class() {
            public function getData(...$args): array
            {
                return  [0, 1, 2, 3];
            }
        };

        $config = $this->parseYaml($config);
        foreach ($config['datasources'] as $name => $dsconfig) {
            if ($dsconfig['service_id'] === 'service_object') {
                $dsconfig['service_id'] = $service;
                $config['datasources'][$name] = $dsconfig;
            }
        }


        $container = new \ArrayObject();
        $context = $this->prophesize(ContextInterface::class);
        $context->toArray()->willReturn([
            'dummy' => 'hello world'
        ]);
        $context->setDatasources(Argument::any())
            ->will(
                function ($args) use ($container) {
                    $container['datasources'] = $args[0];
                }
            );
        $context->getDatasources()->will(function () use ($container) {
            return $container['datasources'];
        });

        $data = new ConfiguratorData($context->reveal());
        $data->setConfig($config);
        $emitter = new RenderArgumentEmitter();

        $instance = new DatasourceConfigurator();
        $instance->preCreateEditables('testbrick', $data);
        $instance->generateDatasources($emitter, $data);

        $renderArguments = $emitter->emit();
        $renderArguments = iterator_to_array($renderArguments);
        self::assertCount(1, $renderArguments);
        self::assertArrayHasKey('testsource', $renderArguments);
        self::assertSame([0, 1, 2, 3], $renderArguments['testsource']->getValue());
    }

    private function buildDatasourceRegistryWithItems($itemCount)
    {
        $items = [];
        $createItem = function ($id) {
            return (object)['id' => $id];
        };

        for ($i = 0; $i < $itemCount; ++$i) {
            $items[] = $createItem($i+1);
        }

        $registry = new DatasourceRegistry();
        $registry->add(
            'test_source',
            function () use ($items) {
                return $items;
            }
        );

        return $registry;
    }

    public function testCanCreateEditable()
    {

        $instance = new DatasourceConfigurator();
        $config = <<<YAML
editable:
  options:
    bla: ''
  datasource:
    name: test_source
    id: item.id
YAML;
        $config = $this->parseYaml($config);
        $itemCount = 5;
        $contextData = [
            'datasources' => $this->buildDatasourceRegistryWithItems($itemCount),
        ];

        $argument = new RenderArgument('editable', 'test', ['options' => ['bla' => '']]);
        $emitter = new RenderArgumentEmitter();
        $emitter->set($argument);

        $context = $this->prophesize(ContextInterface::class);
        $context->toArray()->willReturn($contextData);
        $context->getDatasources()->willReturn($contextData['datasources']);

        $data = new ConfiguratorData($context->reveal());
        $data->setConfig($config['editable']);

        $instance->doCreateEditables($emitter, 'test', $data);
        $actual = iterator_to_array($emitter->emit());

        self::assertCount(2, $actual);

        $types = [];
        $collectionContent = [];
        foreach ($actual as $actualArgument) {
            $types[] = $actualArgument->getType();
            if ($actualArgument->getType() === 'collection') {
                $collectionContent = $actualArgument->getValue();
            }
        }

        $expectedTypes = ['data', 'collection'];
        $this->assertSame($expectedTypes, $types);
        $this->assertCount($itemCount, $collectionContent);
    }

    public function skipCreateEditablesData()
    {
        return [
            [true], [false]
        ];
    }

    /**
     * @dataProvider skipCreateEditablesData
     */
    public function testSkipCreateEditables(bool $withDatasources)
    {
        $config = <<<YAML
areabricks:
  testbrick:
    editables:
      hello:
        type: world
YAML;
        $config = $this->parseYaml($config);

        $context = $this->prophesize(ContextInterface::class);
        $context->toArray()->willReturn([]);
        if ($withDatasources) {
            $context->getDatasources()->willReturn(
                new DatasourceRegistry()
            );
        } else {
            $context->getDatasources()->willReturn(null);
        }

        $argument = new RenderArgument('editable', 'hello', ['type' => 'world']);
        $emitter = new RenderArgumentEmitter();

        $data = new ConfiguratorData($context->reveal());
        $data->setConfig($config['areabricks']['testbrick']);

        $instance = new DatasourceConfigurator();
        $instance->doCreateEditables($emitter, 'test', $data);

        $generator = $emitter->emit();
        $generator = iterator_to_array($generator);

        self::assertFalse($emitter->isArgumentEmitted($argument));
    }

    public function testEditableOptions()
    {
        $instance = new DatasourceConfigurator();
        $or = new OptionsResolver();
        $instance->configureEditableOptions($or);
        self::assertTrue($or->hasDefault('datasource'));
    }

    public function testAlwaysSupports()
    {
        self::assertTrue((new DatasourceConfigurator())->supportsEditable('ignored', ['also ignored']));
    }
}
