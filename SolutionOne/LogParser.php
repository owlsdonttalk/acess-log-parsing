<?php

namespace SolutionOne;

use DateTime;
use SolutionOne\Exception\FileNotSpecifiedException;
use SolutionOne\BasicController;

require_once(realpath(dirname(__FILE__)). '/BasicController.php');
require_once(realpath(dirname(__FILE__)) .'/Exception/FileNotSpecifiedException.php');
/**
 * @property String   $logFile
 * @property DateTime $startParsingFrom
 */
class LogParser extends BasicController
{
    /**
     * @var mixed
     */
    private $logfile;
    /**
     * @var DateTime
     */
    private $parsingStartsFrom;
    /**
     * @var DateTime
     */
    private $parsingEndsAt;
    /**
     * @var int
     */
    private $autosaveStep;
    /**
     * @var int
     */
    private $views       = 0;
    /**
     * @var array
     */
    private $urls        = [];
    /**
     * @var int
     */
    private $traffic     = 0;
    /**
     * @var array
     */
    private $crawlers    = [];
    /**
     * @var array
     */
    private $statusCodes = [];


    /**
     * @param int $autosaveStep
     * @throws FileNotSpecifiedException
     */
    public function __construct(int $autosaveStep = 20)
    {
        $parameters     = $this->parseCommandLineArguments();

        try {
            if (!array_key_exists('file', $parameters)) {
                throw new FileNotSpecifiedException();
            }
        } catch (\SolutionOne\FileNotSpecifiedException $exception){
            echo $exception->getUserMessage() . PHP_EOL;
            return;
        }

        $this->logfile = $parameters['file'];

        if (array_key_exists('from', $parameters)) {
            $this->setParsingStartDate($parameters['from']);
        } else {
            $this->parsingStartsFrom = null;
        }
        if (array_key_exists('to', $parameters)) {
            $this->setEndDate($parameters['to']);
        } else {
            $this->parsingEndsAt = null;
        }

        $this->autosaveStep = $autosaveStep;
    }

    public function startLogProcessing()
    {
        if($this->isFileSpecified()){
            $handle = fopen($this->logfile, "r");
        } else {
            return;
        }

        //@todo implement $this->startSearchFromDate($handle)
//        if($this->startParsingFrom !== null){
//
//        }

        if ($handle) {
            $previousDate = null;

            while (($line = fgets($handle)) !== false) {
                $logLine = new LogLine();
                $logLine->parseLine($line);

                if ($logLine->isLineValid()) {
                    if($this->isEndDateReached($logLine)){
                        break;
                    }

                    $this->views++;
                    $this->urls[$logLine->request] = $logLine->request;
                    $this->traffic                 += $logLine->bodyBytesSent;

                    if ($logLine->isThisBot()) {
                        $this->crawlers[$logLine->detectCrawler()] += 1;
                    }

                    $this->statusCodes[$logLine->status] += 1;

                    if ($this->isItTimeToSaveIntermediateResults()) {
                        $this->saveIntermediateResults($line);
                    }

                    if (is_null($previousDate)) {
                        $previousDate = $logLine->timeLocal();
                    }
                    if ($this->areLinesNotMonotonous($previousDate, $logLine->timeLocal())) {
                        $this->logErrorLine($line, 'Log is not monotonous around this line ');
                    }
                    $previousDate = $logLine->timeLocal();
                } else {
                    if (strlen($line) > 2) {
                        $this->logErrorLine($line, 'Line does not match expected format ');
                    }
                }

            }

            fclose($handle);
        }

        echo json_encode($this->gatherResults());
    }

    /**
     * @return array
     */
    private function parseCommandLineArguments(): array
    {
        $arguments = $_SERVER['argv'];
        $result    = [];
        foreach ($arguments as $argument) {
            $isValidArgumnent = (bool)strpos($argument, "=");

            if ($isValidArgumnent) {
                $parsedArgument             = explode("=", $argument);
                $result[$parsedArgument[0]] = $parsedArgument[1];
            }
        }

        return $result;
    }

    /**
     * @param String $from
     */
    private function setParsingStartDate(string $from)
    {
        $this->parsingStartsFrom =  $this->convertStringToDate($from);
    }

    /**
     * @param String $from
     */
    private function setEndDate(string $to)
    {
        $this->parsingEndsAt = $this->convertStringToDate($to);
    }

    /**
     * @param String $lastLine
     */
    private function saveIntermediateResults(string $lastLine)
    {
        $tempResultsFilename = 'logs/tempResults.log';

        $handle = fopen($tempResultsFilename, 'w');
        if ($handle) {
            $result                     = $this->gatherResults();
            $result['lastParsedString'] = $lastLine;

            fwrite($handle, json_encode($result));
            fclose($handle);

        }
    }

    /**
     * @return array
     */
    private function gatherResults(): array
    {
        $result = [];

        $uniqueUrls = sizeof(array_unique(array_keys($this->urls)));

        $result = [
            'views'       => $this->views,
            'urls'        => $uniqueUrls,
            'traffic'     => $this->traffic,
            'crawlers'    => $this->crawlers,
            'statusCodes' => $this->statusCodes,
        ];


        return $result;
    }

    /**
     * @param String $line
     */
    private function logErrorLine(string $line, string $message)
    {
        $result            = [$message, $line];
        $errorsLogFilename = 'logs/errors.log';

        $handle = fopen($errorsLogFilename, 'a+');
        if ($handle) {
            fwrite($handle, implode($result));
            fclose($handle);
        }
    }

    /**
     * @param DateTime $previousDate
     * @param DateTime      $timeLocal
     * @return bool
     */
    private function areLinesNotMonotonous(DateTime $previousDate, $timeLocal): bool
    {
        return $previousDate > $timeLocal;
    }

    /**
     * @return bool
     */
    private function isItTimeToSaveIntermediateResults(): bool
    {
        return $this->views % $this->autosaveStep == 0;
    }

    /**
     * @param LogLine $logLine
     * @return bool
     */
    private function isEndDateReached(LogLine $logLine): bool
    {
        if($this->parsingEndsAt != null){
            return $logLine->timeLocal() > $this->parsingEndsAt;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function isFileSpecified(): bool
    {
        return !empty($this->logfile);
    }


}