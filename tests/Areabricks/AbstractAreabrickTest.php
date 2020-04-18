<?php

namespace Test\Khusseini\PimcoreRadBrickBundle\Areabricks;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\AreabrickRenderer;
use Khusseini\PimcoreRadBrickBundle\Areabricks\AbstractAreabrick;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Model\Element\Tag;
use Pimcore\Templating\Model\ViewModel;
use Pimcore\Templating\Renderer\TagRenderer;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

class AbstractAreabrickTest extends TestCase
{
    public function testAddsEditablesToView()
    {
        $config = [
            'areabricks' => [
                'testbrick' => [
                    'editables' => [
                        'testedit' => [
                            'type' => 'input'
                        ]
                    ]
                ]
            ]
        ];

        $configurator = new AreabrickConfigurator($config);

        $tagRenderer = $this->prophesize(TagRenderer::class);
        $tag = $this->prophesize(Tag::class);
        $tagRenderer
            ->render(Argument::any(), Argument::cetera())
            ->will(
                function ($args) use($tag) {
                    return $tag->reveal();
                }
            );

        $view = new ViewModel();
        $doc = $this->prophesize(PageSnippet::class);
        $request = $this->prophesize(Request::class);
        $info = $this->prophesize(Info::class);
        $info->getRequest()->willReturn($request->reveal());
        $info->getDocument()->willReturn($doc->reveal());
        $info->getView()->willReturn($view);

        $areabrickRenderer = new AreabrickRenderer($configurator, $tagRenderer->reveal());

        $brick = new class('testbrick', $areabrickRenderer) extends AbstractAreabrick {
        };

        $brick->action($info->reveal());

        $this->assertTrue($view->has('testedit'));
    }

}
