<?php


namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\ConfiguratorData;
use Khusseini\PimcoreRadBrickBundle\Context;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use Pimcore\Templating\Model\ViewModel;
use Symfony\Component\HttpFoundation\Request;
use Tests\Khusseini\PimcoreRadBrickBundle\AbstractTestCase;

abstract class BaseConfiguratorTestCase extends AbstractTestCase
{
    abstract function getInstance(string $case): AbstractConfigurator;

    abstract function getSupportsEditableCases(): array;

    abstract function getPreCreateEditablesData(): array;

    abstract function getDoCreateEditablesData(): array;

    abstract function testConfigureEditableOptions();

    public function getCreateEditablesData(): array
    {
        return [['skip','','',function(){},null,true]];
    }

    abstract function getPostCreateEditablesData(): array;

    public function supportsEditableData()
    {
        return $this->getSupportsEditableCases();
    }

    /**
     * @dataProvider supportsEditableData
     */
    public function testSupportsEditable(
        string $case,
        string $config,
        callable $assert,
        ?string $exception = null,
        bool $skip = false
    ) {
        if ($skip) {
            $this->markTestSkipped();
        }

        if ($exception) {
            $this->expectException($exception);
        }

        $config = $this->parseYaml($config);
        $instance = $this->getInstance($case);
        $actual = $instance->supportsEditable('ignore', $config);
        $assert($actual);
    }

    public function preCreateEditablesData()
    {
        return $this->getPreCreateEditablesData();
    }

    /**
     * @dataProvider preCreateEditablesData
     */
    public function testPreCreateEditables(
        string $case,
        string $config,
        string $brickName,
        callable $assert,
        ?string $exception = null,
        bool $skip = false
    ) {
        if ($skip) {
            $this->markTestSkipped();
        }

        if ($exception) {
            $this->expectException($exception);
        }

        $config = $this->parseYaml($config);
        $context = $this->getContext($case);
        $data = new ConfiguratorData($context);
        $data->setConfig($config);

        $instance = $this->getInstance($case);
        $instance->preCreateEditables($brickName, $data);
        $assert($data);
    }

    public function preDoCreateEditablesData()
    {
        return $this->getDoCreateEditablesData();
    }

    /**
     * @dataProvider preDoCreateEditablesData
     */
    public function testDoCreateEditables(
        string $case,
        string $config,
        string $brickName,
        callable $assert,
        ?string $exception = null,
        bool $skip = false
    ) {
        if ($skip) {
            $this->markTestSkipped();
        }

        if ($exception) {
            $this->expectException($exception);
        }

        $config = $this->parseYaml($config);
        $context = $this->getContext($case);
        $data = new ConfiguratorData($context);
        $data->setConfig($config);

        $emitter = new RenderArgumentEmitter();
        $brick = $config['areabricks'][$brickName];
        foreach ($brick['editables'] as $name => $value) {
            $emitter->set(new RenderArgument('editable', $name, $value));
        }

        $instance = $this->getInstance($case);
        $instance->doCreateEditables($emitter, $brickName, $data);

        $assert($emitter);
    }

    public function createEditablesData()
    {
        return $this->getCreateEditablesData();
    }

    /**
     * @dataProvider createEditablesData
     */
    public function testCreateEditablesData(
        string $case,
        string $config,
        string $brickName,
        callable $assert,
        ?string $exception = null,
        bool $skip = false
    ) {
        if ($skip) {
            $this->markTestSkipped();
        }

        if ($exception) {
            $this->expectException($exception);
        }

        $config = $this->parseYaml($config);
        $context = $this->getContext($case);
        $data = new ConfiguratorData($context);
        $data->setConfig($config);

        $emitter = new RenderArgumentEmitter();
        $brick = $config['areabricks'][$brickName];
        foreach ($brick['editables'] as $name => $value) {
            $emitter->set(new RenderArgument('editable', $name, $value));
        }
        $this->setCreateEditablesArguments($case, $emitter);

        $instance = $this->getInstance($case);
        $instance->createEditables($emitter, $brickName, $data);

        $assert($emitter);
    }

    protected function setCreateEditablesArguments(string $case, RenderArgumentEmitter $emitter): void
    {

    }

    public function postCreateEditablesData()
    {
        return $this->getPostCreateEditablesData();
    }

    /**
     * @dataProvider postCreateEditablesData
     */
    public function testPostCreateEditablesData(
        string $case,
        string $config,
        string $brickName,
        callable $assert,
        ?string $exception = null,
        bool $skip = false
    ) {
        if ($skip) {
            $this->markTestSkipped();
        }

        if ($exception) {
            $this->expectException($exception);
        }

        $config = $this->parseYaml($config);
        $context = $this->getContext($case);
        $data = new ConfiguratorData($context);
        $data->setConfig($config);

        $emitter = new RenderArgumentEmitter();
        $brick = $config['areabricks'][$brickName];
        foreach ($brick['editables'] as $name => $value) {
            $emitter->set(new RenderArgument('editable', $name, $value));
        }
        $this->setPostCreateEditablesArguments($case, $emitter);

        $instance = $this->getInstance($case);
        $instance->postCreateEditables($brickName, $brick, $emitter);

        $assert($emitter);
    }

    protected function setPostCreateEditablesArguments(string $case, RenderArgumentEmitter $emitter): void
    {

    }


    protected function getContext(string $case)
    {
        $view = new ViewModel();
        $request = $this->prophesize(Request::class);
        return new Context($view, $request->reveal());
    }
}
