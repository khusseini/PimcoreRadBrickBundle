<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupConfigurator extends AbstractConfigurator
{
    public function configureEditableOptions(OptionsResolver $or): void
    {
        $or->setDefault('group', null);
        $or->setAllowedValues(
            'group', function ($value) {
                if (is_null($value)) {
                    return true;
                }
                return preg_match('/[_a-z]+/i', $value);
            }
        );
    }

    public function supportsEditable(string $editableName, array $config): bool
    {
        return isset($config['group']);
    }

    /**
     * @param  array<string,mixed> $config
     * @return array<string,mixed>
     */
    protected function resolveBrickConfig(array $config): array
    {
        $or = new OptionsResolver();
        $or->setDefined(array_keys($config));
        $or->setDefault('groups', []);
        $or->setDefault('editables', []);
        return $or->resolve($config);
    }

    public function preCreateEditables(string $brickName, ConfiguratorData $data): void
    {
        $config = $data->getConfig();
        $brick = $this->resolveBrickConfig($config['areabricks'][$brickName]);

        $groups = $brick['groups'];

        $editables = $brick['editables'];

        foreach ($editables as $name => $editable) {
            if (!isset($editable['group'])) {
                continue;
            }

            if (!isset($groups[$editable['group']])) {
                throw new \InvalidArgumentException("Group with name {$editable['group']} does not exist.");
            }
            $groupConfig = $groups[$editable['group']];
            $editable = array_merge($editable, $groupConfig);
            $editables[$name] = $editable;
        }

        $brick['editables'] = $editables;
        $config['areabricks'][$brickName] = $brick;
        $data->setConfig($config);
    }

    public function doCreateEditables(RenderArgumentEmitter $emitter, string $name, ConfiguratorData $data): void
    {
        return;
    }

    public function postCreateEditables(string $brickName, array $config, RenderArgumentEmitter $emitter): void
    {
        if (!$config['groups']) {
            return;
        };

        $groups = array_keys($config['groups']);

        $groupArguments = [];

        foreach ($config['editables'] as $name => $config) {
            if (!in_array($config['group'], $groups)) {
                continue;
            }
            if (!$emitter->has($name)) {
                continue;
            }
            $groupName = $config['group'];
            if (!isset($groupArguments[$groupName])) {
                $groupArguments[$groupName] = [];
            }
            $renderArg = $emitter->get($name);

            if ($renderArg->getType() === 'collection') {
                $values = $renderArg->getValue();

                foreach ($values as $key => $data) {
                    $referenceId = $name.'_'.$key;
                    $groupArguments[$groupName][$key][$name] = new RenderArgument('reference', $name, $referenceId);
                }
            } else {
                if (!isset($groupArguments[$groupName][0])) {
                    $groupArguments[$groupName][0] = [];
                }
                $groupArguments[$groupName][0][$renderArg->getName()] =
                    new RenderArgument(
                        'reference',
                        $renderArg->getName(),
                        $renderArg->getName()
                    );
            }
        }

        foreach ($groupArguments as $name => $data) {
            $argumentValue = [];
            foreach ($data as $key => $value) {
                $argumentValue[$key] = new RenderArgument('collection', $key, $value);
            }

            $argument = new RenderArgument('collection', $name, $argumentValue);
            $emitter->emitArgument($argument);
        }
    }
}
