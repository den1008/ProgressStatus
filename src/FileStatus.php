<?php

namespace den1008\ProgressStatus;

use den1008\ProgressStatus\classes\AbstractStatus;

/**
 * Класс вывода статуса в файл
 *
 * @package app\components\processStatus
 */
class FileStatus extends AbstractStatus
{
    /** @var string Путь до файла */
    protected $file;

    /**
     * @var string Формат вывода
     */
    public $format = "[{%date%} {%time%}][{%commonPercent%}] {%msg%}";

    /**
     * FileStatus constructor.
     * @param string $file Путь до файла
     * @param bool $clearFile Флаг очистки файла перед началом работы. Если true то файл будет очищен.
     * @throws \Exception
     */
    public function __construct($file, $clearFile = false)
    {
        parent::__construct();
        $this->setFile($file);
        if ($clearFile) {
            file_put_contents($this->file, '');
        }
    }

    /**
     * Установка пути до файла
     * @param $file
     * @return $this
     * @throws \Exception
     */
    public function setFile($file){
        if(!is_writeable($file)){
            throw new \Exception("$file недоступен для чтения");
        }
        return $this;
    }

    /** @param mixed $msg */
    protected function sayConcrete($msg)
    {
        if (empty($msg)) {
            return;
        }

        $msg = $this->formatMessage($msg, $this->format);
        file_put_contents($this->file, $msg . PHP_EOL, FILE_APPEND);
    }

    public function sayStageConcrete($stage)
    {
        $this->sayConcrete($stage);
    }
}
