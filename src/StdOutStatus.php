<?php

namespace den1008\ProgressStatus;

use den1008\ProgressStatus\classes\AbstractStatus;

/**
 * Класс вывода статуса в консоль
 *
 * @package app\components\processStatus
 */
class StdOutStatus extends AbstractStatus
{
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
        parent::__construct();
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

    protected function sayConcrete($msg)
    {
        if (empty($msg)) {
            return;
        }

        //Добавляем новую строку для однострочного (скрытого) прогресса
        if ($this->getNested() >= $this->hideNestedAfterDepth
            && $this->lastNested < $this->getNested()
        ) {
            self::stdout(PHP_EOL);
        }

        $msg = $this->formatMessageWithNested($msg);
        $msg = $this->cutMessage($msg);

        self::stdout($msg . PHP_EOL);
        $this->lastNested = $this->getNested();
    }

    public function sayStageConcrete($stage)
    {
        $this->sayConcrete($stage);
    }

    /**
     * Функция форматирования однострочного прогресса
     * @param $msg
     * @return mixed
     * @throws \Exception
     */
    protected function formatMessageWithNested($msg)
    {
        $format = $this->mainFormat;
        if ($this->getNested() >= $this->hideNestedAfterDepth) {
            //Очистка строки и возврат курсора в начало
            $format = "\033[A\033[2K" . $this->hideFormat;
        }

        return parent::formatMessage($msg, $format);
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
