<?php

require 'vendor/autoload.php';

use Exam\Command\ExamCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new ExamCommand());
$application->run();
