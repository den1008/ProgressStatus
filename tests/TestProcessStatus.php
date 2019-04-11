<?php

namespace den1008\ProgressStatus\tests;

use den1008\ProgressStatus\classes\AbstractStatus;

/**
 * Class TestProcessStatus
 *
 * @package tests
 */
class TestProcessStatus extends AbstractStatus
{
    public $countSay = 0;

    protected function sayConcrete($msg)
    {
        $this->countSay++;
    }

    public function sayStageConcrete($stage)
    {
    }
}
