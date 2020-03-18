<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Sensio\Bundle\FrameworkExtraBundle\Security\ExpressionLanguage;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractConfigurator implements IConfigurator
{
    /** @var ExpressionLanguage */
    private $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage = null)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    protected function getExpressionLanguage(): ExpressionLanguage
    {
        if (!$this->expressionLanguage) {
            $this->expressionLanguage = new ExpressionLanguage();
        }

        return $this->expressionLanguage;
    }

    abstract public function doProcessConfig(
        string $action,
        OptionsResolver $or,
        array $data
    );

    public function getExpressionAttributes(): array
    {
        return [];
    }

    public function processConfig(string $action, OptionsResolver $or, array $data)
    {
        $attributes = $this->getExpressionAttributes();
        $propAccess = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor()
        ;
        $target = $data['editable']['config'];
        foreach ($attributes as $attributePath) {
            try {
                $value = $propAccess->getValue($target, $attributePath);
            } catch (AccessException $ex) {
                continue;
            }

            $value = $this->processValue($value, @$data['context'] ?: []);
            $propAccess->setValue($target, $attributePath, $value);
            $data['editable']['config'] = $target;
        }

        return $this->doProcessConfig(
            $action,
            $or,
            $data
        );
    }

    public function processValue($value, array $context)
    {
        try {
            return $this->getExpressionLanguage()->evaluate($value, $context);
        } catch (\Exception $ex) {
            return $value;
        }
    }
}
