<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\EventListener\DetermineWinnerSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DetermineWinnerSubscriberTest extends TestCase
{
    /**
     * @var MockObject|HitRepository
     */
    private $hitRepository;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var DetermineWinnerSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hitRepository = $this->createMock(HitRepository::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);
        $this->subscriber    = new DetermineWinnerSubscriber($this->hitRepository, $this->translator);
    }

    public function testOnDetermineBounceRateWinner(): void
    {
        $parentMock    = $this->createMock(Page::class);
        $childMock     = $this->createMock(Page::class);
        $children      = [2 => $childMock];
        $transChildren = $this->createMock(Collection::class);
        $ids           = [1, 3];
        $parameters    = ['parent' => $parentMock, 'children' => $children];
        $event         = new DetermineWinnerEvent($parameters);
        $startDate     = new \DateTime();
        $translation   = 'bounces';

        $bounces = [
            1 => [
                'totalHits' => 20,
                'bounces'   => 5,
                'rate'      => 25,
                'title'     => 'Page 1.1',
                ],
            2 => [
                'totalHits' => 10,
                'bounces'   => 1,
                'rate'      => 10,
                'title'     => 'Page 1.2',
                ],
            3 => [
                'totalHits' => 30,
                'bounces'   => 15,
                'rate'      => 50,
                'title'     => 'Page 2.1',
            ],
            4 => [
                'totalHits' => 10,
                'bounces'   => 5,
                'rate'      => 50,
                'title'     => 'Page 2.2',
            ],
        ];

        $this->translator
            ->method('trans')
            ->willReturn($translation);

        $parentMock
            ->method('hasTranslations')
            ->willReturn(1);

        $childMock
            ->method('hasTranslations')
            ->willReturn(1);

        $transChildren->method('getKeys')
            ->willReturnOnConsecutiveCalls([2], [4]);

        $parentMock
            ->method('getTranslationChildren')
            ->willReturn($transChildren);

        $childMock
            ->method('getTranslationChildren')
            ->willReturn($transChildren);

        $parentMock->expects(self::once())
            ->method('getRelatedEntityIds')
            ->willReturn($ids);

        $parentMock
            ->method('getId')
            ->willReturn(1);

        $childMock
            ->method('getId')
            ->willReturn(3);

        $parentMock->expects(self::once())
            ->method('getVariantStartDate')
            ->willReturn($startDate);

        $this->hitRepository->expects(self::once())
            ->method('getBounces')
            ->with($ids, $startDate)
            ->willReturn($bounces);

        $this->subscriber->onDetermineBounceRateWinner($event);

        $expectedData = [20, 50];

        $abTestResults = $event->getAbTestResults();

        // Check for lowest bounce rates
        self::assertEquals([1], $abTestResults['winners']);
        self::assertEquals($expectedData, $abTestResults['support']['data'][$translation]);
    }

    public function testOnDetermineDwellTimeWinner()
    {
        $parentMock  = $this->createMock(Page::class);
        $ids         = [1, 2];
        $parameters  = ['parent' => $parentMock];
        $event       = new DetermineWinnerEvent($parameters);
        $startDate   = new \DateTime();
        $translation = 'dewlltime';

        $counts = [
            1 => [
                'sum'     => 1000,
                'min'     => 5,
                'max'     => 200,
                'average' => 50,
                'count'   => 10,
                'title'   => 'title',
            ],
            2 => [
                'sum'     => 2000,
                'min'     => 10,
                'max'     => 300,
                'average' => 70,
                'count'   => 50,
                'title'   => 'title',
            ],
        ];

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn($translation);

        $parentMock->expects($this->once())
            ->method('getRelatedEntityIds')
            ->willReturn($ids);

        $parentMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $parentMock->expects($this->once())
            ->method('getVariantStartDate')
            ->willReturn($startDate);

        $this->hitRepository->expects($this->once())
            ->method('getDwellTimesForPages')
            ->with($ids, ['fromDate' => $startDate])
            ->willReturn($counts);

        $this->subscriber->onDetermineDwellTimeWinner($event);

        $expectedData = [50, 70];

        $abTestResults = $event->getAbTestResults();

        $this->assertEquals($abTestResults['winners'], [2]);
        $this->assertEquals($abTestResults['support']['data'][$translation], $expectedData);
    }
}
