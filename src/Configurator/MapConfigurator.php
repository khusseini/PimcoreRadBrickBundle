<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class MapConfigurator extends AbstractConfigurator
{
    public function configureEditableOptions(OptionsResolver $or): void
    {
        $or->setDefault('map', []);
    }

    public function supports(string $action, string $editableName, array $config): bool
    {
        return
            $action === self::ACTION_CREATE_EDIT
            && count($config['map'])
        ;
    }

    private $mapOr;
    private function resolveMapOptions($options)
    {
        if (!$this->mapOr) {
            $this->mapOr = new OptionsResolver();
            $this->mapOr->setRequired(['source', 'target']);
        }

        return $this->mapOr->resolve($options);
    }

    private $propAccess;
    private function getPropAccess(): PropertyAccessor
    {
        if (!$this->propAccess) {
            $this->propAccess = PropertyAccess::createPropertyAccessorBuilder()
                ->enableExceptionOnInvalidIndex()
                ->getPropertyAccessor()
            ;
        }

        return $this->propAccess;
    }

    private function writeProperty($context, $path, $value)
    {
        $pa = $this->getPropAccess();
        $pa->setValue($context, $path, $value);
        return $context;
    }

    public function doProcessConfig(string $action, RenderArgs $renderArgs, array $data): RenderArgs
    {
        if (!$this->supports($action, $data['editable']['name'], $data['editable']['config'])) {
            return $renderArgs;
        }

        $maps = $data['editable']['config']['map'];
        foreach ($maps as $map) {
            $map = $this->resolveMapOptions($map);
            $source = $this->processValue($map['source'], $data['context']);
            $data['editable']['config'] = $this->writeProperty($data['editable']['config'], $map['target'], $source);
        }

        $renderArgs->update([$data['editable']['name'] => $data['editable']['config']]);
        return $renderArgs;
    }
}
