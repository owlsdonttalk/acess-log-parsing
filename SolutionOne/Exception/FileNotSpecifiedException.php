<?php

namespace SolutionOne\Exception;

require_once('AbstractBasicException.php');

class FileNotSpecifiedException extends AbstractBasicException implements HumanReadableInterface
{
    private $userMessage = 'File not specified! Add "file=filename" parameter to init string';

    public function __construct($message = null, $code = 0)
    {
        parent::__construct('File not specified', $code);
        $this->message = $message;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
}