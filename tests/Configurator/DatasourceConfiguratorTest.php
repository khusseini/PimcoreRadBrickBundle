<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\ConfiguratorData;
use Khusseini\PimcoreRadBrickBundle\Configurator\DatasourceConfigurator;
use Khusseini\PimcoreRadBrickBundle\ContextInterface;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatasourceConfiguratorTest extends BaseConfiguratorTestCase
{
    /**
     * @return DatasourceConfigurator
     */
    public function getInstance(string $case): AbstractConfigurator
    {
        return new DatasourceConfigurator();
    }

    public function getSupportsEditableCases(): array
    {
        $positive = <<<YAML
datasource:
  name: products
  id: item.getId()
YAML;

        return [
            [
                'positive',
                $positive,
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
        $simpleConfig = <<<YAML
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
        $recursiveConfig = <<<YAML
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
          second: world
YAML;

        return [
            [
                'simple',
                $simpleConfig,
                'testbrick',
                function (ConfiguratorData $data) {
                    $datasources = $data->getContext()->getDatasources();
                    self::assertTrue($datasources->has('testsource'));
                },
            ], [
                'recursive',
                $recursiveConfig,
                'testbrick',
                function (ConfiguratorData $data) {
                    $datasources = $data->getContext()->getDatasources();
                    self::assertTrue($datasources->has('testsource'));
                },
            ],
        ];
    }

    public function getDoCreateEditablesData(): array
    {
        $simpleConfig = <<<YAML
areabricks:
  testbrick:
    editables:
      testeditable:
        options:
          bla: ''
        datasource:
          name: test_source
YAML;
        $invalidDatasource = <<<YAML
areabricks:
  testbrick:
    editables:
      testeditable:
        options:
          bla: ''
        datasource:
          name: doesnotexist
YAML;
        $skipEditable = <<<YAML
areabricks:
  testbrick:
    editables:
      testeditable:
        options:
          bla: ''
YAML;

        return [
            [
                'simple_config',
                $simpleConfig,
                'testbrick',
                function (RenderArgumentEmitter $emitter) {
                    $actual = iterator_to_array($emitter->emit());
                    self::assertCount(2, $actual);

                    $types = [];
                    $collectionContent = [];
                    foreach ($actual as $actualArgument) {
                        $types[] = $actualArgument->getType();
                        if ('collection' === $actualArgument->getType()) {
                            $collectionContent = $actualArgument->getValue();
                        }
                    }

                    $expectedTypes = ['data', 'collection'];
                    $this->assertSame($expectedTypes, $types);
                    $this->assertCount(4, $collectionContent);
                },
            ], [
                'no_datasource',
                $simpleConfig,
                'testbrick',
                function (RenderArgumentEmitter $emitter) {
                    $actual = iterator_to_array($emitter->emit());
                    self::assertCount(0, $actual);
                },
            ], [
                'simple_config',
                $invalidDatasource,
                'testbrick',
                function (RenderArgumentEmitter $emitter) {
                    $actual = iterator_to_array($emitter->emit());
                    self::assertCount(1, $actual);
                },
            ], [
                'simple_config',
                $skipEditable,
                'testbrick',
                function (RenderArgumentEmitter $emitter) {
                    $actual = iterator_to_array($emitter->emit());
                    self::assertCount(0, $actual);
                },
            ],
        ];
    }

    public function getPostCreateEditablesData(): array
    {
        return [['skip', '', '', function () {
        }, null, true]];
    }

    public function testConfigureEditableOptions()
    {
        $or = new OptionsResolver();
        $instance = $this->getInstance('');
        $instance->configureEditableOptions($or);
        self::assertTrue($or->isDefined('datasource'));
    }

    protected function getContext(string $case)
    {
        $context = parent::getContext($case);
        if ('no_datasources' === $case) {
            return $context;
        }
        $service = new class() {
            public function getData(...$args): array
            {
                return  [0, 1, 2, 3];
            }
        };
        $wrapper = new class($context, $service) implements ContextInterface {
            private $context;
            private $serviceObject;

            public function __construct(ContextInterface $context, $service)
            {
                $this->context = $context;
                $this->serviceObject = $service;
            }

            public function setDatasources(DatasourceRegistry $datasourceRegistry): void
            {
                $this->context->setDatasources($datasourceRegistry);
            }

            public function getDatasources(): ?DatasourceRegistry
            {
                return $this->context->getDatasources();
            }

            public function toArray(): array
            {
                $inner = $this->context->toArray();
                $inner['service_object'] = $this->serviceObject;

                return $inner;
            }
        };

        if ('simple_config' === $case) {
            $registry = new DatasourceRegistry();
            $context->setDatasources($registry);

            $registry->add('test_source', function () use ($service) {
                return $service->getData();
            });
        }

        return $wrapper;
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
            [$recursiveconfig],
        ];
    }

    /**
     * @dataProvider canGenerateDatasourcesData
     */
    public function testCanGenerateDatasources(string $config)
    {
        $config = $this->parseYaml($config);

        $context = $this->getContext(__METHOD__);

        $data = new ConfiguratorData($context);
        $data->setConfig($config);

        $emitter = new RenderArgumentEmitter();

        $instance = $this->getInstance(__METHOD__);
        $instance->preCreateEditables('testbrick', $data);
        $instance->generateDatasources($emitter, $data);

        $renderArguments = $emitter->emit();
        $renderArguments = iterator_to_array($renderArguments);
        self::assertCount(1, $renderArguments);
        self::assertArrayHasKey('testsource', $renderArguments);
        self::assertSame([0, 1, 2, 3], $renderArguments['testsource']->getValue());
    }
}
