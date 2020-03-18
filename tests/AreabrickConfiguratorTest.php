<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Tests;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\IConfigurator;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Document\PageSnippet;
use Prophecy\Argument;

class AreabrickConfiguratorTest extends TestCase
{
    /** @var PageSnippet */
    private $pageSnippet;

    private function getPageSnippet(): PageSnippet
    {
        if (!$this->pageSnippet) {
            $this->pageSnippet = $this->prophesize(PageSnippet::class)->reveal();
        }

        return $this->pageSnippet;
    }

    protected function getSimpleBrickTestData(): array
    {
        $expected = [
            'wysiwyg_content' => [
                'type' => 'wysiwyg',
                'options' => ['random' => 'option']
            ]
        ];

        return [
            ['areabricks' => [
                'wysiwyg' => [
                    'editables' => $expected
                ]
            ]],
            function ($areabrick, $editables) use ($expected) {
                $this->assertEquals('wysiwyg', $areabrick);
                foreach ($editables as $name => $args) {
                    $this->assertArrayHasKey($name, $expected);
                    $expectedConfig = $expected[$name];
                    $this->assertSame($expectedConfig, $args);
                }
            },
        ];
    }

    public function canCreateEditablesProvider()
    {
        return [
            $this->getSimpleBrickTestData(),
            $this->getIConfiguratorIntegrationData(),
            $this->getIConfiguratorIntegrationData(false),
        ];
    }

    /**
     * @dataProvider canCreateEditablesProvider
     */
    public function testCanCreateEditables($config, $tests, $configurators = [])
    {
        $configurator = $this->createConfigurator($config);
        $configurator->setConfigurators($configurators);
        $areabricks = $config['areabricks'];
        foreach ($areabricks as $areabrick => $aconfig) {
            $editables = $configurator->createEditables($areabrick, $this->getPageSnippet());
            $tests($areabrick, $editables);
        }
    }

    private function createIConfig($supports = true)
    {
        $configuratorInterface = $this->prophesize(IConfigurator::class);
        $configuratorInterface
            ->supports(Argument::cetera())
            ->willReturn($supports)
        ;
        $configuratorInterface
            ->configureEditableOptions(Argument::any())
        ;
        return $configuratorInterface;
    }

    public function getIConfiguratorIntegrationData($supports = true)
    {
        $configuratorInterface = $this->createIConfig($supports);
        $name = 'testeditable'. (!$supports ? '': '_tampered');
        $expected = [
            $name => [
                'type' => 'input',
                'options' => [],
            ]
        ];
        $config =[
            'areabricks' => [
                'testbrick' => [
                    'editables' => [
                        'testeditable' => [
                            'type' => 'input',
                            'options' => [],
                        ]
                    ]
                ]
            ]
        ];

        $configuratorInterface
            ->processConfig(Argument::any(), Argument::cetera())
            ->willReturn($expected)
        ;

        $assert = function ($areabrick, $editables) use ($expected) {
            foreach ($editables as $name => $args) {
                $this->assertArrayHasKey($name, $expected);
                $expectedConfig = $expected[$name];
                $this->assertSame($expectedConfig, $args);
            }
        };
        $ci = $configuratorInterface->reveal();
        return [$config, $assert, [$ci]];
    }

    private function createConfigurator(array $config)
    {
        return new AreabrickConfigurator(
            $config
        );
    }
}
