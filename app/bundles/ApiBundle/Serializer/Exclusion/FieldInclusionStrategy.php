<?php

namespace Mautic\ApiBundle\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

/**
 * Class FieldInclusionStrategy.
 *
 * Include specific fields at a specific level
 */
class FieldInclusionStrategy implements ExclusionStrategyInterface
{
    private int $level;

    private $path;

    /**
     * FieldInclusionStrategy constructor.
     *
     * @param int  $level
     * @param null $path
     */
    public function __construct(private array $fields, $level = 3, $path = null)
    {
        $this->level  = (int) $level;
        $this->path   = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext): bool
    {
        if ($this->path) {
            $path = implode('.', $navigatorContext->getCurrentPath());
            if ($path !== $this->path) {
                return false;
            }
        }

        $name = $property->serializedName ?: $property->name;
        if (in_array($name, $this->fields)) {
            return false;
        }

        // children of children or parents of chidlren will be more than 3 levels deep
        if ($navigatorContext->getDepth() <= $this->level) {
            return false;
        }

        return true;
    }
}
