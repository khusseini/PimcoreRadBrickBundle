<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Tests;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\TagRenderer;
use Prophecy\Argument;

class AreabrickConfiguratorTest extends TestCase
{
    public function testCanCreateEditables()
    {
        $config = [
            'areabricks' => [
                'wysiwyg' => [
                    'editables' => [
                        'wysiwyg_content' => [
                            'type' => 'wysiwyg',
                            'options' => [
                                'random' => 'option'
                            ]
                        ],
                    ],
                ],
            ],
        ];

        $tagRenderer = $this->prophesize(TagRenderer::class);
        $tagRenderer->render(Argument::cetera())
            ->will(
                function ($args) {
                    return $args;
                }
            );

        $configurator = new AreabrickConfigurator($tagRenderer->reveal(), $config);
        $doc = $this->prophesize(PageSnippet::class);
        $revealedDoc = $doc->reveal();
        $expected = [
            'wysiwyg_content' => [
                $revealedDoc,
                'wysiwyg',
                'wysiwyg_content',
                ['random' => 'option']
            ],
            
        ];

        $editables = $configurator->createEditables('wysiwyg', $revealedDoc);
        
        foreach ($editables as $name => $content) {
            self::assertArrayHasKey($name, $expected);
            $expectedContent = $expected[$name];
            self::assertSame($expectedContent, $content);
        }
    }
}
