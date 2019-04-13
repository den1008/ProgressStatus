<?php

namespace den1008\ProgressStatus\handlers;


use den1008\ProgressStatus\StatusProcessor;

/**
 * Trait FormatMessageTrait
 * Методы форматирования сообщений AbstractStatus
 * @package app\components\processStatus\classes
 */
trait FormatMessageTrait
{
    /**
     * Форматирование сообщения
     * @param StatusProcessor $processor
     * @param $msg
     * @param string $format Формат вывода
     * {%date%} - текущая дата
     * {%time%} - текущее время
     * {%progress%} - текущий прогресс
     * {%total%} - максимальный прогресс
     * {%time%} - текущее время
     * {%percent%} - текущий процент выполнения
     * {%commonPercent%} - общий процент выполнения
     * {%totalTime%} - общее время (от начала момента работы) выполнения на каждом шаге
     * {%stepTime%} - затраченое время (от момента последнего шага) на каждом шаге
     * {%msg%} - сообщение
     *
     * @return mixed
     */
    public function formatMessage(StatusProcessor $processor, $msg, $format)
    {
        $time = microtime(true);

        $this->replaceIfExist($format, '{%msg%}', function () use ($msg) {
            return $msg;
        });
        $this->replaceIfExist($format, '{%date%}', function () {
            return date("Y-m-d");
        });
        $this->replaceIfExist($format, '{%time%}', function () {
            return date("H:i:s");
        });
        $this->replaceIfExist($format, '{%progress%}', function () use ($processor){
            return $processor->getProgress();
        });
        $this->replaceIfExist($format, '{%total%}', function () use ($processor){
            return $processor->getTotal();
        });
        $this->replaceIfExist($format, '{%percent%}', function () use ($processor){
            return sprintf('%4.1F', $processor->getPercent());
        });
        $this->replaceIfExist($format, '{%commonPercent%}', function () use ($processor){
            return sprintf('%4.1F', $processor->getCommonPercent());
        });
        $this->replaceIfExist($format, '{%totalTime%}', function () use ($processor, $time) {
            $fromStart = $time - $processor->getTimeStart();
            return sprintf('%8.2F', $fromStart);
        });
        $this->replaceIfExist($format, '{%stepTime%}', function () use ($processor, $time) {
            $fromLast = $time - $processor->getTimeLast();
            return sprintf('%7.3F', $fromLast);
        });

        return $format;
    }

    protected function replaceIfExist(&$str, $pattern, \Closure $func)
    {
        if (strstr($str, $pattern)) {
            $str = str_replace($pattern, $func($str), $str);
        }
    }
}
