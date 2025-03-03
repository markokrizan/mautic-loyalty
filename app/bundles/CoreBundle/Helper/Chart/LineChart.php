<?php

namespace Mautic\CoreBundle\Helper\Chart;

/**
 * Class LineChart.
 *
 * Line chart requires the same data as Bar chart
 */
class LineChart extends AbstractChart implements ChartInterface
{
    /**
     * Match date/time unit to a humanly readable label
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}.
     *
     * @var array
     */
    protected $labelFormats = [
        's' => 'H:i:s',
        'i' => 'H:i',
        'H' => 'M j ga',
        'd' => 'M j, y',
        'D' => 'M j, y', // ('D' is BC. Can be removed when all charts use this class)
        'W' => '\W\e\e\k W', // (Week is escaped here so it's not interpreted when creating labels)
        'm' => 'M Y',
        'M' => 'M Y', // ('M' is BC. Can be removed when all charts use this class)
        'Y' => 'Y',
    ];

    /**
     * Defines the basic chart values, generates the time axe labels from it.
     *
     * @param string|null $unit       {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime   $dateFrom
     * @param \DateTime   $dateTo
     * @param string      $dateFormat
     */
    public function __construct(?string $unit = null, $dateFrom = null, $dateTo = null, protected $dateFormat = null)
    {
        $this->unit       = (null === $unit) ? $this->getTimeUnitFromDateRange($dateFrom, $dateTo) : $unit;
        $this->isTimeUnit = in_array($this->unit, ['H', 'i', 's']);
        $this->setDateRange($dateFrom, $dateTo);
        $this->amount     = $this->countAmountFromDateRange();
        $this->generateTimeLabels($this->amount);
        $this->addOneUnitMinusOneSec($this->dateTo);
    }

    /**
     * @return array{labels: mixed[], datasets: mixed[]}
     */
    public function render(): array
    {
        return [
            'labels'   => $this->labels,
            'datasets' => $this->datasets,
        ];
    }

    /**
     * Define a dataset by name and data. Method will add the rest.
     *
     * @param string $label
     *
     * @return $this
     */
    public function setDataset($label, array $data)
    {
        $datasetId = count($this->datasets);

        $baseData = [
            'label' => $label,
            'data'  => $data,
        ];

        $this->datasets[] = array_merge($baseData, $this->generateColors($datasetId));

        return $this;
    }

    /**
     * Generate array of labels from the form data.
     *
     * @param int $amount
     */
    public function generateTimeLabels($amount)
    {
        if (!isset($this->labelFormats[$this->unit])) {
            throw new \UnexpectedValueException('Date/Time unit "'.$this->unit.'" is not available for a label.');
        }

        /** @var \DateTime $date */
        $date    = clone $this->dateFrom;
        $oneUnit = $this->getUnitInterval();
        $format  = !empty($this->dateFormat) ? $this->dateFormat : $this->labelFormats[$this->unit];

        for ($i = 0; $i < $amount; ++$i) {
            $this->labels[] = $date->format($format);

            // Special case for months because PHP behaves weird with February
            if ('m' === $this->unit) {
                $date->modify('first day of next month');
            } else {
                $date->add($oneUnit);
            }
        }
    }

    /**
     * Generate unique color for the dataset.
     *
     * @param int $datasetId
     */
    public function generateColors($datasetId): array
    {
        $color = $this->configureColorHelper($datasetId);

        return [
            'backgroundColor'           => $color->toRgba(0.1),
            'borderColor'               => $color->toRgba(0.8),
            'pointHoverBackgroundColor' => $color->toRgba(0.75),
            'pointHoverBorderColor'     => $color->toRgba(1),
        ];
    }
}
