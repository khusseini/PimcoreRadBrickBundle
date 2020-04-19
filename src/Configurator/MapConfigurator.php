<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MapConfigurator extends AbstractConfigurator
{
    /**
     * @codeCoverageIgnore
     */
    public function configureEditableOptions(OptionsResolver $or): void
    {
        $or->setDefault('map', []);
    }

    public function supportsEditable(string $editableName, array $config): bool
    {
        return (bool) count($config['map']);
    }

    /**
     * @param  array<array> $options
     * @return array<array>
     */
    private function resolveMapOptions(array $options): array
    {
        $or = new OptionsResolver();
        $or->setRequired(['source', 'target']);
        return $or->resolve($options);
    }

    public function doCreateEditables(RenderArgumentEmitter $emitter, string $name, ConfiguratorData $data): void
    {
        $argument = $emitter->get($name);
        $config = $data->getConfig();
        if ($this->supportsEditable($name, $config)) {
            $maps = $config['map'];
            foreach ($maps as $map) {
                /**
                 * @var array<string> $map
                 */
                $map = $this->resolveMapOptions($map);
                $source = $this->getExpressionWrapper()->evaluateExpression($map['source'], $data->getContext()->toArray());
                $config = $this
                    ->getExpressionWrapper()
                    ->setPropertyValue($config, $map['target'], $source);
            }

            $argument = new RenderArgument(
                $argument->getType(),
                $argument->getName(),
                $config['options']
            );
        }

        $emitter->emitArgument($argument);
    }
}
