<?php

namespace Mautic\DashboardBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Widget>
 */
class WidgetRepository extends CommonRepository
{
    /**
     * Update widget ordering.
     *
     * @param array $ordering
     * @param int   $userId
     */
    public function updateOrdering($ordering, $userId): void
    {
        $widgets = $this->getEntities(
            [
                'filter' => [
                    'createdBy' => $userId,
                ],
            ]
        );

        foreach ($widgets as &$widget) {
            if (isset($ordering[$widget->getId()])) {
                $widget->setOrdering((int) $ordering[$widget->getId()]);
            }
        }

        $this->saveEntities($widgets);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['w.ordering', 'ASC'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias(): string
    {
        return 'w';
    }
}
