<?php

namespace Khusseini\PimcoreRadBrickBundle\ExpressionLanguage;

use Khusseini\PimcoreRadBrickBundle\Context;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ExpressionWrapper
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var PropertyAccessorInterface
     */
    private $propAccess;

    private function getExpressionLanguage(): ExpressionLanguage
    {
        if (is_null($this->expressionLanguage)) {
            $this->expressionLanguage = new ExpressionLanguage();
        }

        return $this->expressionLanguage;
    }

    private function getPropertyAccess(): PropertyAccessorInterface
    {
        if (is_null($this->propAccess)) {
            $this->propAccess = PropertyAccess::createPropertyAccessorBuilder()
                ->enableExceptionOnInvalidIndex()
                ->getPropertyAccessor();
        }

        return $this->propAccess;
    }

    /**
     * @param array<mixed>  $data
     * @param array<string> $attributes
     *
     * @return array<mixed>
     */
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

    /**
     * @param array<array>|object $objectOrArray
     *
     * @return mixed
     */
    public function getPropertyValue($objectOrArray, string $propertyPath)
    {
        return $this
            ->getPropertyAccess()
            ->getValue($objectOrArray, $propertyPath);
    }

    /**
     * @param array<array>|object $objectOrArray
     * @param mixed               $value
     *
     * @return array<array>|object
     */
    public function setPropertyValue($objectOrArray, string $propertyPath, $value)
    {
        $this->getPropertyAccess()->setValue($objectOrArray, $propertyPath, $value);

        return $objectOrArray;
    }

    /**
     * @param array<array> $context
     *
     * @return mixed
     */
    public function evaluateExpression(string $value, array $context)
    {
        try {
            return $this->getExpressionLanguage()->evaluate($value, $context);
        } catch (\Exception $ex) {
            return $value;
        }
    }
}
