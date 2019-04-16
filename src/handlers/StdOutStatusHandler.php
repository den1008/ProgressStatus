<?php

namespace den1008\ProgressStatus\handlers;

use den1008\ProgressStatus\handlers\IStatusHandler;
use den1008\ProgressStatus\StatusProcessor;

/**
 * Класс вывода статуса в консоль
 *
 * @package app\components\processStatus
 */
class StdOutStatusHandler implements IStatusHandler
{
	use FormatMessageTrait;

    /** @var int Максимальная длина консольной строки */
    protected $maxStrLength = 120;

    /** @var string Формат вывода */
    public $mainFormat = "[{%date%} {%time%}][{%commonPercent%}%][{%totalTime%}] {%msg%}";

    /** @var string Формат вывода скрытых однострочных статусов */
    public $hideFormat = "[{%date%} {%time%}][{%commonPercent%}%][{%totalTime%}] └ {%msg%}";

    /** @var int Скрывать вложенные выводы статусы после заданной глубины */
    protected $hideNestedAfterDepth = 1;

    /** @var int Предыдущая глубина вложенности */
    private $lastNested;

    public function __construct($hideNestedAfterDepth = 1)
    {
        $this->hideNestedAfterDepth = $hideNestedAfterDepth;
        if ($this->hideNestedAfterDepth == 0) {
            self::stdout(PHP_EOL);
        }
    }

    /**
     * @param int $maxStrLength
     * @return $this
     * @throws \Exception
     */
    public function setMaxStrLength(int $maxStrLength)
    {
        if ($maxStrLength < 0) {
            throw new \Exception("Максимальная длина консольной строки должна быть больше 0");
        }

        $this->maxStrLength = $maxStrLength;
        return $this;
    }

    public function sayConcrete(StatusProcessor $processor, $msg)
    {
        if (empty($msg)) {
            return;
        }

        //Добавляем новую строку для однострочного (скрытого) прогресса
        if ($processor->getNested() >= $this->hideNestedAfterDepth
            && $this->lastNested < $processor->getNested()
        ) {
            self::stdout(PHP_EOL);
        }

        $msg = $this->formatMessageWithNested($processor, $msg);
        $msg = $this->cutMessage($msg);

        self::stdout($msg . PHP_EOL);
        $this->lastNested = $processor->getNested();
    }

    public function sayStageConcrete(StatusProcessor $processor, $stage)
    {
        $this->sayConcrete($processor, $stage);
    }

    /**
     * Функция форматирования однострочного прогресса
	 * @param StatusProcessor $processor
     * @param $msg
     * @return mixed
     * @throws \Exception
     */
    protected function formatMessageWithNested(StatusProcessor $processor, $msg)
    {
        $format = $this->mainFormat;
        if ($processor->getNested() >= $this->hideNestedAfterDepth) {
            //Очистка строки и возврат курсора в начало
            $format = "\033[A\033[2K" . $this->hideFormat;
        }

        return $this->formatMessage($processor, $msg, $format);
    }

    /**
     * Обрезание сообщения до нужной длины
     * @param string $message
     * @return string
     */
    protected function cutMessage(string $message)
    {
        return mb_strlen($message) > $this->maxStrLength
            ?
            mb_substr($message, 0, $this->maxStrLength - 3) . '...'
            :
            $message;
    }

    /**
     * Печать строки в STDOUT.
     * @param string $string строка, которую необходимо напечатать
     * @return int|bool Количество напечатанных символов или false при возникновении ошибки
     */
    protected static function stdout($string)
    {
        return fwrite(\STDOUT, $string);
    }
}
