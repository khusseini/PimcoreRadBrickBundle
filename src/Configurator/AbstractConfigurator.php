<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Sensio\Bundle\FrameworkExtraBundle\Security\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

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

    public function processValue($value, array $context)
    {
        try {
            return $this->getExpressionLanguage()->evaluate($value, $context);
        } catch (\Exception $ex) {
            return $value;
        }
    }
}
