<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MapConfigurator extends AbstractConfigurator
{
    /** @var OptionsResolver */
    private $mapOr;

    public function configureEditableOptions(OptionsResolver $or): void
    {
        $or->setDefault('map', []);
    }

    public function supportsEditable(string $editableName, array $config): bool
    {
        return (bool) count($config['map']);
    }

    private function resolveMapOptions($options)
    {
        if (!$this->mapOr) {
            $this->mapOr = new OptionsResolver();
            $this->mapOr->setRequired(['source', 'target']);
        }

        return $this->mapOr->resolve($options);
    }

    public function doCreateEditables(RenderArgs $renderArgs, array $data): RenderArgs
    {
        if (!$this->supportsEditable($data['editable']['name'], $data['editable']['config'])) {
            return $renderArgs;
        }

        $maps = $data['editable']['config']['map'];
        foreach ($maps as $map) {
            $map = $this->resolveMapOptions($map);
            $source = $this->getExpressionWrapper()->evaluateExpression($map['source'], $data['context']);
            $data['editable']['config'] = $this
                ->getExpressionWrapper()
                ->setPropertyValue($data['editable']['config'], $map['target'], $source)
            ;
        }

        $renderArgs->update([$data['editable']['name'] => $data['editable']['config']]);
        return $renderArgs;
    }
}
