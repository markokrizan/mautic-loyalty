<?php

namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Entity\TriggerEventRepository;
use Mautic\PointBundle\Form\Type\TriggerEventType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * @extends CommonFormModel<TriggerEvent>
 */
class TriggerEventModel extends CommonFormModel
{
    /**
     * {@inheritdoc}
     *
     * @return TriggerEventRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(\Mautic\PointBundle\Entity\TriggerEvent::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase(): string
    {
        return 'point:triggers';
    }

    /**
     * {@inheritdoc}
     *
     * @return TriggerEvent|null
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new TriggerEvent();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof TriggerEvent) {
            throw new MethodNotAllowedHttpException(['Trigger']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(TriggerEventType::class, $entity, $options);
    }

    /**
     * Get segments which are dependent on given segment.
     *
     * @param int $segmentId
     *
     * @return array
     */
    public function getReportIdsWithDependenciesOnSegment($segmentId)
    {
        $filter = [
            'force'  => [
                ['column' => 'e.type', 'expr' => 'eq', 'value'=>'lead.changelists'],
            ],
        ];
        $entities = $this->getEntities(
            [
                'filter'     => $filter,
            ]
        );
        $dependents = [];
        foreach ($entities as $entity) {
            $retrFilters = $entity->getProperties();
            foreach ($retrFilters as $eachFilter) {
                if (in_array($segmentId, $eachFilter)) {
                    $dependents[] = $entity->getTrigger()->getId();
                }
            }
        }

        return $dependents;
    }
}
