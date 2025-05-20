<?php
namespace Lightpack\AI;

interface ProviderInterface
{
    /**
     * Generate AI/ML output (text, etc.) based on input parameters.
     * @param array $params
     * @return mixed
     */
    public function generate(array $params);

    // Optionally, add more methods for analysis, image, etc.
}
