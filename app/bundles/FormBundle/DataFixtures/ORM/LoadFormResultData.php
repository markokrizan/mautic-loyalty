<?php

namespace Mautic\FormBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\PageBundle\Model\PageModel;

class LoadFormResultData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(private PageModel $pageModel, private SubmissionModel $submissionModel)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $importResults = function ($results): void {
            foreach ($results as $rows) {
                $submission = new Submission();
                $submission->setDateSubmitted(new \DateTime());

                foreach ($rows as $col => $val) {
                    if ('NULL' != $val) {
                        $setter = 'set'.\ucfirst($col);
                        if (\in_array($col, ['form', 'page', 'ipAddress', 'lead'])) {
                            if ('lead' === $col) {
                                // For some reason the lead must be linked with id - 1
                                $entity = $this->getReference($col.'-'.($val - 1));
                            } else {
                                $entity = $this->getReference($col.'-'.$val);
                            }
                            if ('page' == $col) {
                                $submission->setReferer($this->pageModel->generateUrl($entity));
                            }
                            $submission->$setter($entity);
                            unset($rows[$col]);
                        } else {
                            // the rest are custom field values
                            break;
                        }
                    }
                }

                $submission->setResults($rows);
                $this->submissionModel->getRepository()->saveEntity($submission);
            }
        };

        $results = CsvHelper::csv_to_array(__DIR__.'/fakeresultdata.csv');
        $importResults($results);

        \sleep(2);

        $results2 = CsvHelper::csv_to_array(__DIR__.'/fakeresult2data.csv');
        $importResults($results2);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 9;
    }
}
