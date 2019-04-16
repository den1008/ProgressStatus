<?php

namespace den1008\ProgressStatus\handlers;

use den1008\ProgressStatus\StatusProcessor;

interface IStatusHandler
{
    /**
     * Рассказать о текущем прогрессе выполнения конкретному экзкмпляру статуса
	 * @param StatusProcessor $processor
     * @param $msg
     */
    public function sayConcrete(StatusProcessor $processor, $msg);


    /**
     * Рассказать о текущем прогрессе выполнения конкретному экзкмпляру статуса
	 * @param StatusProcessor $processor
     * @param $msg
     */
	public function sayStageConcrete(StatusProcessor $processor, $msg);
}
