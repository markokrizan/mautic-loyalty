<?php

namespace Mautic\DynamicContentBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Serializer;

/**
 * @extends CommonRepository<DynamicContent>
 */
class DynamicContentRepository extends CommonRepository
{
    /**
     * Get a list of entities.
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select('e')
            ->from(DynamicContent::class, 'e', 'e.id');

        if (empty($args['iterator_mode'])) {
            $q->leftJoin('e.category', 'c');
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        list($expr, $parameters) = $this->addStandardSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        list($expr, $parameters) = parent::addSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = false; // returning a parameter that is not used will lead to a Doctrine error

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.lang'):
                $langUnique      = $this->generateRandomParameterName();
                $langValue       = $filter->string.'_%';
                $forceParameters = [
                    $langUnique => $langValue,
                    $unique     => $filter->string,
                ];
                $expr = $q->expr()->orX(
                    $q->expr()->eq('e.language', ":$unique"),
                    $q->expr()->like('e.language', ":$langUnique")
                );
                break;
        }

        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif ($returnParameter) {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = ["$unique" => $string];
        }

        return [$expr, $parameters];
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        $commands = [
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.isuncategorized',
            'mautic.core.searchcommand.ismine',
            'mautic.core.searchcommand.category',
            'mautic.core.searchcommand.lang',
        ];

        return array_merge($commands, parent::getSearchCommands());
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['e.name', 'ASC'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias(): string
    {
        return 'e';
    }

    /**
     * Up the sent counts.
     *
     * @param int $increaseBy
     */
    public function upSentCount($id, $increaseBy = 1): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'dynamic_content')
            ->set('sent_count', 'sent_count + '.(int) $increaseBy)
            ->where('id = '.(int) $id);

        $q->executeStatement();
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param bool   $viewOther
     * @param bool   $topLevel
     * @param array  $ignoreIds
     * @param string $where
     *
     * @return array
     */
    public function getDynamicContentList($search = '', $limit = 10, $start = 0, $viewOther = false, $topLevel = false, $ignoreIds = [], $where = null)
    {
        $q = $this->createQueryBuilder('e');
        $q->select('partial e.{id, name, language}');

        if (!empty($search)) {
            if (is_array($search)) {
                $search = array_map('intval', $search);
                $q->andWhere($q->expr()->in('e.id', ':search'))
                  ->setParameter('search', $search);
            } else {
                $q->andWhere($q->expr()->like('e.name', ':search'))
                  ->setParameter('search', "%{$search}%");
            }
        }

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('e.createdBy', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        if ('translation' == $topLevel) {
            // only get top level pages
            $q->andWhere($q->expr()->isNull('e.translationParent'));
        } elseif ('variant' == $topLevel) {
            $q->andWhere($q->expr()->isNull('e.variantParent'));
        }

        if (!empty($ignoreIds)) {
            $q->andWhere($q->expr()->notIn('e.id', ':dwc_ids'))
                ->setParameter('dwc_ids', $ignoreIds);
        }

        if ($where) {
            $q->andWhere($where);
        }

        $q->orderBy('e.name');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    /**
     * @return bool|object|null
     */
    public function getDynamicContentForSlotFromCampaign($slot)
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();

        $qb->select('ce.properties')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_events', 'ce')
            ->leftJoin('ce', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = ce.campaign_id')
            ->andWhere($qb->expr()->eq('ce.type', $qb->expr()->literal('dwc.decision')))
            ->andWhere($qb->expr()->like('ce.properties', ':slot'))
            ->setParameter('slot', '%'.$slot.'%')
            ->orderBy('c.is_published');

        $result = $qb->executeQuery()->fetchAllAssociative();

        foreach ($result as $item) {
            $properties = Serializer::decode($item['properties']);

            if (isset($properties['dynamicContent'])) {
                $dwc = $this->getEntity($properties['dynamicContent']);

                if ($dwc instanceof DynamicContent) {
                    return $dwc;
                }
            }
        }

        return false;
    }
}
