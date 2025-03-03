<?php

namespace Mautic\PageBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Model\CategoryModel;

class LoadPageCategoryData extends AbstractFixture implements OrderedFixtureInterface
{
    public function __construct(private CategoryModel $categoryModel)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $today  = new \DateTime();
        $cat    = new Category();
        $events = 'Events';

        $cat->setBundle('page');
        $cat->setDateAdded($today);
        $cat->setTitle($events);
        $cat->setAlias(strtolower($events));

        $this->categoryModel->getRepository()->saveEntity($cat);
        $this->setReference('page-cat-1', $cat);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 6;
    }
}
