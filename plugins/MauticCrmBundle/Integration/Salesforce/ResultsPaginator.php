<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce;

use Mautic\PluginBundle\Exception\ApiErrorException;
use Psr\Log\LoggerInterface;

class ResultsPaginator
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $results;

    /**
     * @var int
     */
    private $totalRecords = 0;

    /**
     * @var int
     */
    private $recordCount = 0;

    /**
     * @var int
     */
    private $retryCount = 0;

    /**
     * @var string|null
     */
    private $nextRecordsUrl;

    /**
     * @var string
     */
    private $salesforceBaseUrl;

    /**
     * @param string $salesforceBaseUrl
     */
    public function __construct(LoggerInterface $logger, $salesforceBaseUrl)
    {
        $this->logger            = $logger;
        $this->salesforceBaseUrl = $salesforceBaseUrl;
    }

    /**
     * @return $this
     *
     * @throws ApiErrorException
     */
    public function setResults(array $results)
    {
        if (!isset($results['records'])) {
            throw new ApiErrorException(var_export($results, true));
        }

        $this->results      = $results;
        $this->totalRecords = $results['totalSize'];
        $this->recordCount += count($results['records']);

        return $this;
    }

    /**
     * @return string
     *
     * @throws ApiErrorException
     */
    public function getNextResultsUrl()
    {
        if (isset($this->results['nextRecordsUrl'])) {
            $this->retryCount     = 0;
            $this->nextRecordsUrl = $this->results['nextRecordsUrl'];

            if (!str_contains($this->nextRecordsUrl, $this->salesforceBaseUrl)) {
                $this->nextRecordsUrl = $this->salesforceBaseUrl.$this->nextRecordsUrl;
            }

            return $this->nextRecordsUrl;
        }

        if ($this->recordCount < $this->totalRecords) {
            // Something has gone wrong so try a few more times before giving up
            if ($this->retryCount <= 5) {
                $this->logger->debug("SALESFORCE: Processed less than total but didn't get a nextRecordsUrl in the response: ".var_export($this->results, true));

                usleep(500);
                ++$this->retryCount;

                // Try again
                return $this->nextRecordsUrl;
            }

            // Throw an exception cause something isn't right
            throw new ApiErrorException("Expected to process {$this->totalRecords} but only processed {$this->recordCount}: ".var_export($this->results, true));
        }

        $this->nextRecordsUrl = null;

        return '';
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return (int) $this->totalRecords;
    }
}
