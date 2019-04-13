<?php

namespace den1008\ProgressStatus\tests;

use den1008\ProgressStatus\StatusProcessor;
use den1008\ProgressStatus\tests\classes\TestProcessStatusHandler;
use PHPUnit\Framework\TestCase;

/**
 * Class ProcessStatusTest
 *
 * @package tests
 */
class HandlesTest extends TestCase
{
    protected function getInstance(): StatusProcessor
    {
        return new StatusProcessor();
    }


}
