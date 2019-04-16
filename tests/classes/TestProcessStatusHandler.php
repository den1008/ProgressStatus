<?php

namespace den1008\ProgressStatus\tests\classes;

use den1008\ProgressStatus\handlers\IStatusHandler;
use den1008\ProgressStatus\StatusProcessor;

/**
 * Class TestProcessStatus
 *
 * @package tests
 */
class TestProcessStatusHandler implements IStatusHandler
{
    public $countSay = 0;


    public function sayConcrete(StatusProcessor $processor, $msg)
	{
		$this->countSay++;
	}

	public function sayStageConcrete(StatusProcessor $processor, $msg)
	{

	}
}
