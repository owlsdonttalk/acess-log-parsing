<?php

use SolutionOne\LogParser;

require_once('LogParser.php');
require_once('BasicController.php');
require_once('LogLine.php');

$logParser = new LogParser();
$logParser->startLogProcessing();

$t =2;
?>
