<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Form\DataTransformer;

use Sonata\AdminBundle\Form\ChoiceList\ModelChoiceList;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Class ModelsToArrayTransformer.
 *
 * @author  Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class ModelsToArrayTransformer implements DataTransformerInterface
{
    protected $choiceList;

    /**
     * @param \Sonata\AdminBundle\Form\ChoiceList\ModelChoiceList $choiceList
     */
    public function __construct(ModelChoiceList $choiceList)
    {
        $this->choiceList = $choiceList;
    }

    /**
     * {@inheritDoc}
     */
    public function transform($collection)
    {
        if (null === $collection) {
            return array();
        }

        $array = array();

        if (count($this->choiceList->getIdentifier()) > 1) {
            // load all choices
            $availableEntities = $this->choiceList->getEntities();

            foreach ($collection as $entity) {
                // identify choices by their collection key
                $key = array_search($entity, $availableEntities);
                $array[] = $key;
            }
        } else {
            foreach ($collection as $entity) {
                $array[] = current($this->choiceList->getIdentifierValues($entity));
            }
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($keys)
    {
        $collection = $this->choiceList->getModelManager()->getModelCollectionInstance(
            $this->choiceList->getClass()
        );

        if (!$collection instanceof \ArrayAccess) {
            throw new UnexpectedTypeException($collection, '\ArrayAccess');
        }

        if ('' === $keys || null === $keys) {
            return $collection;
        }

        if (!is_array($keys)) {
            throw new UnexpectedTypeException($keys, 'array');
        }

        $notFound = array();

        // optimize this into a SELECT WHERE IN query
        foreach ($keys as $key) {
            if ($entity = $this->choiceList->getEntity($key)) {
                $collection[] = $entity;
            } else {
                $notFound[] = $key;
            }
        }

        if (count($notFound) > 0) {
            throw new TransformationFailedException(sprintf('The entities with keys "%s" could not be found', implode('", "', $notFound)));
        }

        return $collection;
    }
}
