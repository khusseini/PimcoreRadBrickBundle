<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\Renderer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MapConfigurator extends AbstractConfigurator
{
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

    public function doCreateEditables(Renderer $renderer, string $name, array $data): void
    {
        $argument = $renderer->get($name);
        if ($this->supportsEditable($name, $data['editable'])) {
            $maps = $data['editable']['map'];
            foreach ($maps as $map) {
                /**
 * @var array<string> $map 
*/
                $map = $this->resolveMapOptions($map);
                $source = $this->getExpressionWrapper()->evaluateExpression($map['source'], $data['context']);
                $data['editable'] = $this
                    ->getExpressionWrapper()
                    ->setPropertyValue($data['editable'], $map['target'], $source);
            }

            $argument = new RenderArgument(
                $argument->getType(),
                $argument->getName(),
                $data['editable']['options']
            );
        }

        $renderer->emitArgument($argument);
    }
}
