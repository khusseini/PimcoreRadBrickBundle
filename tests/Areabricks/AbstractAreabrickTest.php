<?php

namespace Test\Khusseini\PimcoreRadBrickBundle\Areabricks;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\Areabricks\AbstractAreabrick;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag\Area\Info;
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
        $tagRenderer
            ->render(Argument::any(), Argument::cetera())
            ->will(function ($args) {
                return $args;
            })
        ;

        $view = new ViewModel();
        $doc = $this->prophesize(PageSnippet::class);
        $request = $this->prophesize(Request::class);
        $info = $this->prophesize(Info::class);
        $info->getRequest()->willReturn($request->reveal());
        $info->getDocument()->willReturn($doc->reveal());
        $info->getView()->willReturn($view);

        $brick = new class('testbrick', $tagRenderer->reveal(), $configurator) extends AbstractAreabrick { };
        $brick->action($info->reveal());

        $this->assertTrue($view->has('testedit'));
    }
}
