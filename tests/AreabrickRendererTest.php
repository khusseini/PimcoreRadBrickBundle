<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\AreabrickRenderer;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Model\Element\Tag;
use Pimcore\Templating\Model\ViewModel;
use Pimcore\Templating\Renderer\TagRenderer;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;

class AreabrickRendererTest extends TestCase
{
    use ProphecyTrait;

    public function testAddsEditablesToView()
    {
        $configurator = $this->prophesize(AreabrickConfigurator::class);
        $configurator
            ->hasAreabrickConfig(Argument::any())
            ->willReturn(true);

        $configurator
            ->compileAreaBrick(Argument::any(), Argument::cetera())
            ->will(
                function (...$args) {
                    yield 'testeditable' => new RenderArgument('editable', 'testeditable', ['type' => 'text']);
                    yield 'null' => new RenderArgument('null', 'nope');
                    yield 'testeditable_collection' => new RenderArgument(
                        'collection',
                        'testeditable_collection',
                        [
                        new RenderArgument('editable', 'collection_item', ['type' => 'text']),
                        ]
                    );
                    yield 'testreference' => new RenderArgument('reference', 'testreference', 'testeditable');
                    yield 'some_data' => new RenderArgument('data', 'some_data', ['hello' => 'world']);
                    yield 'shouldnot_be_here' => new RenderArgument('editable', 'shouldnot_be_here', null);
                    yield 'shouldnot_be_here2' => new RenderArgument('editable', 'shouldnot_be_here', []);
                }
            );

        $tagRenderer = $this->getTagRenderer();
        $info = $this->getInfo()->reveal();

        $areabrickRenderer = new AreabrickRenderer(
            $configurator->reveal(),
            $tagRenderer->reveal()
        );

        $areabrickRenderer->render('testbrick', $info);
        $view = $info->getView();

        $this->assertTrue($view->has('testeditable'));
        $this->assertTrue($view->has('testeditable_collection'));
    }

    public function testGetAreabrickConfig()
    {
        $config = [
            'areabricks' => [
                'testbrick' => [
                    'editables' => [
                        'testedit' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $configurator = new AreabrickConfigurator($config);
        $tagRenderer = $this->getTagRenderer();
        $areabrickRenderer = new AreabrickRenderer(
            $configurator,
            $tagRenderer->reveal()
        );

        $actual = $areabrickRenderer->getAreabrickConfig('testbrick');
        self::assertArrayHasKey('testedit', $actual['editables']);
        self::assertArrayHasKey('type', $actual['editables']['testedit']);
        self::assertEquals('input', $actual['editables']['testedit']['type']);
    }

    public function testRenderExceptionOnInvalidBrickName()
    {
        $this->expectException(\InvalidArgumentException::class);

        $configurator = $this->prophesize(AreabrickConfigurator::class);
        $configurator
            ->hasAreabrickConfig(Argument::any())
            ->willReturn(false);
        $tagRenderer = $this->getTagRenderer();
        $info = $this->getInfo()->reveal();

        $areabrickRenderer = new AreabrickRenderer(
            $configurator->reveal(),
            $tagRenderer->reveal()
        );

        $areabrickRenderer->render('testbrick', $info);
    }

    protected function getTagRenderer()
    {
        $tagRenderer = $this->prophesize(TagRenderer::class);
        $tag = $this->prophesize(Tag::class);
        $tagRenderer
            ->render(Argument::any(), Argument::cetera())
            ->will(
                function (...$args) use ($tag) {
                    return $tag->reveal();
                }
            );

        return $tagRenderer;
    }

    protected function getInfo()
    {
        $view = new ViewModel();
        $doc = $this->prophesize(PageSnippet::class);
        $request = $this->prophesize(Request::class);
        $info = $this->prophesize(Info::class);
        $info->getRequest()->willReturn($request->reveal());
        $info->getDocument()->willReturn($doc->reveal());
        $info->getView()->willReturn($view);

        return $info;
    }
}
