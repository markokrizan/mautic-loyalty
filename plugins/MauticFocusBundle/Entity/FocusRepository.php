<?php

namespace MauticPlugin\MauticFocusBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Focus>
 */
class FocusRepository extends CommonRepository
{
    /**
     * @return array
     */
    public function findByForm($formId)
    {
        return $this->findBy(
            [
                'form' => (int) $formId,
            ]
        );
    }

    public function getEntities(array $args = [])
    {
        $alias = $this->getTableAlias();

        $q = $this->_em
            ->createQueryBuilder()
            ->select($alias)
            ->from(\MauticPlugin\MauticFocusBundle\Entity\Focus::class, $alias, $alias.'.id');

        if (empty($args['iterator_mode'])) {
            $q->leftJoin($alias.'.category', 'c');
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['f.name', 'f.website']);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     *
     * @return array
     */
    protected function addSearchCommandWhereClause($q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.name', 'ASC'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'f';
    }

    /**
     * @return array
     */
    public function getFocusList($currentId)
    {
        $q = $this->createQueryBuilder('f');
        $q->select('partial f.{id, name, description}')->orderBy('f.name');

        return $q->getQuery()->getArrayResult();
    }
}
