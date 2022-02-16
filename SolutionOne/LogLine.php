<?php

namespace SolutionOne;

use DateTime;
use SolutionOne\BasicController;

require_once('BasicController.php');

/**
 * @property array  $matches
 * @property string $remoteAddress
 * @property string $timeLocal
 * @property string $request
 * @property int $status
 * @property int $bodyBytesSent
 * @property string $httpRefferer
 * @property string $httpUserAgent
 * @property array  $crawlers
 */
class LogLine extends BasicController
{
    /**
     * @var array
     */
    public $matches = [];
    /**
     * @var string
     */
    public $remoteAddress;
    /**
     * @var string
     */
    public $timeLocal;
    /**
     * @var string
     */
    public $request;
    /**
     * @var int
     */
    public $status;
    /**
     * @var int
     */
    public $bodyBytesSent;
    /**
     * @var string
     */
    public $httpRefferer;
    /**
     * @var string
     */
    public $httpUserAgent;

    /**
     * @var array
     */
    private $crawlers = [
        'Google',
        'Bing',
        'Baidu',
        'Yandex'
    ];


    /**
     * @param String $line
     */
    public function parseLine(string $line)
    {
        $regexPattern = '#^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b).*\[(.*)\]\s"(.*)"\s(\d{3})\s(\d*)\s"(.*)"\s"(.*)"#U';
        preg_match("{$regexPattern}", $line, $this->matches);

        if (!empty($this->matches)) {
            $this->hydrateValuesFromLine();
        }
    }

    public function hydrateValuesFromLine()
    {
        $this->remoteAddress = $this->matches[1];
        $this->timeLocal     = $this->matches[2];
        $this->request       = $this->matches[3];
        $this->status        = $this->matches[4];
        $this->bodyBytesSent = $this->matches[5];
        $this->httpRefferer  = $this->matches[6];
        $this->httpUserAgent = $this->matches[7];
    }

    /**
     * @return bool
     */
    public function isLineValid(): bool
    {
        return !empty($this->matches);
    }

    /**
     * @return DateTime|false
     */
    public function timeLocal()
    {
        $date     = explode(" ", $this->timeLocal);
        return $this->convertStringToDate($date[0], $date[1]);
    }

    /**
     * @return bool
     */
    public function isThisBot(): bool
    {
        return !(strpos($this->httpUserAgent, 'bot') === false);
    }

    /**
     * @return string
     */
    public function detectCrawler(): string
    {
        $result = '';

        foreach ($this->crawlers as $crawler) {
            if (strpos($this->httpUserAgent, $crawler)) {
                $result = $crawler;
                break;
            }
        }

        return $result;
    }

}