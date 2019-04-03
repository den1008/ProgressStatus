<?php

namespace den1008\ProgressStatus;

use den1008\ProgressStatus\classes\AbstractStatus;

/**
 * Класс статуса заглушки
 *
 * @package app\components\processStatus
 */
class StubStatus extends AbstractStatus
{
    protected function sayConcrete($msg)
    {
    }

    public function sayStage($stage)
    {
    }
}
