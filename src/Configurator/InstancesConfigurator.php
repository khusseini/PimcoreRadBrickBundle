<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Symfony\Component\OptionsResolver\OptionsResolver;

class InstancesConfigurator implements IConfigurator
{
    public function setEditableDefaults(OptionsResolver $or): void
    {
        $or->setDefault('instances', null);
    }
}
