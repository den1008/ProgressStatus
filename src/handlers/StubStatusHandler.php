<?php

namespace den1008\ProgressStatus\handlers;

use den1008\ProgressStatus\handlers\IStatusHandler;
use den1008\ProgressStatus\StatusProcessor;

/**
 * Класс статуса заглушки
 *
 * @package app\components\processStatus
 */
class StubStatusHandler implements IStatusHandler
{
    public function sayConcrete(StatusProcessor $processor, $msg)
	{

	}

	public function sayStageConcrete(StatusProcessor $processor, $msg)
	{

	}
}
