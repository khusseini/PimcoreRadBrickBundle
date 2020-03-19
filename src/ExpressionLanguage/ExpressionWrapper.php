<?php

namespace Khusseini\PimcoreRadBrickBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ExpressionWrapper
{
    /** @var ExpressionLanguage */
    private $expressionLanguage;

    /** @var PropertyAccess */
    private $propAccess;

    private function getExpressionLanguage(): ExpressionLanguage
    {
        if (!$this->expressionLanguage) {
            $this->expressionLanguage = new ExpressionLanguage();
        }

        return $this->expressionLanguage;
    }

    private function getPropertyAccess()
    {
        if (!$this->propAccess) {
            $this->propAccess = PropertyAccess::createPropertyAccessorBuilder()
                ->enableExceptionOnInvalidIndex()
                ->getPropertyAccessor()
            ;
        }

        return $this->propAccess;
    }


    public function evaluateExpressions(
        array $data,
        array $attributes,
        string $contextPath = ''
    ): array {
        $context = $data;
        if ($contextPath) {
            $context = $this->getPropertyValue($data, $contextPath);
        }

        foreach ($attributes as $attributePath) {
            try {
                $value = $this->getPropertyValue($data, $attributePath);
            } catch (AccessException $ex) {
                continue;
            }

            $value = $this->evaluateExpression($value, $context);
            $data = $this->setPropertyValue($data, $attributePath, $value);
        }

        return $data;
    }

    public function getPropertyValue($objectOrArray, string $propertyPath)
    {
        return $this
            ->getPropertyAccess()
            ->getValue($objectOrArray, $propertyPath)
        ;
    }

    public function setPropertyValue($objectOrArray, string $properyPath, $value)
    {
        $this->getPropertyAccess()->setValue($objectOrArray, $properyPath, $value);
        return $objectOrArray;
    }

    public function evaluateExpression($value, array $context)
    {
        try {
            return $this->getExpressionLanguage()->evaluate($value, $context);
        } catch (\Exception $ex) {
            return $value;
        }
    }
}
