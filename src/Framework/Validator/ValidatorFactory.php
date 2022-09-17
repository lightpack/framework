<?php

namespace Lightpack\Validator;

use ReflectionClass;
use RuntimeException;
use ReflectionException;

/**
 * A factory for generating rules objects within 
 * Lightpack\Validator\Rules namespace.
 */
class ValidatorFactory
{
    /**
     * Holds an instance of strategy class.
     *
     * @var RuleInterface
     */
    private $rule = null;

    /**
     * Class constructor.
     *
     * @access  public
     * @param   string  $rule  The name of rule to be produced.
     * @throws  RuntimeException
     * @todo    Cache reflected objects for future references
     */
    public function __construct($rule)
    {
        $rule = ucfirst($rule);

        try {
            $reflection = new ReflectionClass("Lightpack\Validator\Rules\\$rule");
        } catch (ReflectionException $e) {
            throw new RuntimeException(sprintf("No class exists for rule: %s", $rule));
        }

        if (!$reflection->implementsInterface('Lightpack\Validator\RuleInterface')) {
            throw new RuntimeException(sprintf("The class defined for rule: %s must implement interface: RuleInterface", $rule));
        }

        // things are fine, let us produce our rule instance
        $this->rule = $reflection->newInstance();
    }

    /**
     * This method is called to get the instance.
     *
     * @access  public
     * @return  object  The rule object.
     */
    public function getRule()
    {
        return $this->rule;
    }
}
