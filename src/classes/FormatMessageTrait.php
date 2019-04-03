<?php

namespace den1008\ProgressStatus\classes;


/**
 * Trait FormatMessageTrait
 * Методы форматирования сообщений AbstractStatus
 * @package app\components\processStatus\classes
 */
trait FormatMessageTrait
{
    /**
     * Форматирование сообщения
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
    protected function formatMessage($msg, $format)
    {
        if (!($this instanceof AbstractStatus)) {
            throw new \Exception("Данный trait может быть использован только с AbstractStatus");
        }
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
        $this->replaceIfExist($format, '{%progress%}', function () {
            return $this->progress;
        });
        $this->replaceIfExist($format, '{%total%}', function () {
            return $this->total;
        });
        $this->replaceIfExist($format, '{%percent%}', function () {
            return sprintf('%4.1F', $this->getPercent());
        });
        $this->replaceIfExist($format, '{%commonPercent%}', function () {
            return sprintf('%4.1F', $this->getCommonPercent());
        });
        $this->replaceIfExist($format, '{%totalTime%}', function () use ($time) {
            $fromStart = $time - $this->timeStart;
            return sprintf('%8.2F', $fromStart);
        });
        $this->replaceIfExist($format, '{%stepTime%}', function () use ($time) {
            $fromLast = $time - $this->timeLast;
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
