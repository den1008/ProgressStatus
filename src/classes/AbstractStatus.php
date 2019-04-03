<?php

namespace den1008\ProgressStatus\classes;

abstract class AbstractStatus
{
    use FormatMessageTrait;

    /** @var float Время начала работы в Unix timestamp with microseconds */
    protected $timeStart = 0;

    /** @var float Время последнего вызова в Unix timestamp with microseconds */
    protected $timeLast = 0;

    /** @var int Текущий прогресс */
    protected $progress = 0;

    /** @var int Общее число прогресса */
    protected $total = 0;

    /** @var float Последний процент выполнения */
    protected $lastPercent = 0;

    /** @var int Текущий процент выполнения */
    protected $percent = 0;

    /** @var array Переменная для хранения данных о вложенных подпрограммах */
    private $subProcesses = [];

    /**
     * @var int Шаг (в процентах) с которым обрабатываются сообщения попавшие в метод say(). Если 0, то обрабатываются все сообщения
     * Например, если $stepViewProgress == 10, то обрабатываться будут сообщения только с 10.12, 20.56, 30.98, ..., 100 процентами,
     * т.е. остаток от деления на $stepViewProgress меньше 1. Если будет несколько сообщений подходящими под условие, например,
     * 10.12, 10.34. 10.56, 10.89, то будет обработано только первое.
     */
    protected $stepProcessingProgress = 0;

    public function __construct()
    {
        $this->timeStart = microtime(true);
        $this->timeLast = $this->timeStart;
    }

    /**
     * @return int
     */
    public function getStepProcessingProgress(): int
    {
        return $this->stepProcessingProgress;
    }

    /**
     * @param int $stepProcessingProgress
     * @return $this
     */
    public function setStepProcessingProgress(int $stepProcessingProgress)
    {
        if($stepProcessingProgress < 0 || $stepProcessingProgress > 100){
            throw new \Exception("Шаг обработки сообщений должен быть в диапазоне от 0 до 100");
        }
        $this->stepProcessingProgress = $stepProcessingProgress;
        return $this;
    }

    /**
     * Получение текущей вложенности подпрограммы
     * @return int
     */
    public function getNested()
    {
        return count($this->subProcesses);
    }

    /**
     * Подсчет статистики во вложенных подпрограммах
     * Пример использования
     *   $stat->setTotal(1000);
     *   //{%total%} = 1000
     *   for ($i = 0; $i <= 20; $i += 10) {
     *      $stat->setProgress($i * 10);
     *      //$i=0  -->  {%percent%} = 0%,  {%progress%} = 0,   {%commonPercent%} = 0%
     *      //$i=10 -->  {%percent%} = 10%, {%progress%} = 100, {%commonPercent%} = 10%
     *      //$i=20 -->  {%percent%} = 20%, {%progress%} = 200, {%commonPercent%} = 20%
     *
     *      $stat->say('Процесс 1 {%percent%}% элементов ({%progress%} из {%total%})');
     *   }
     *   //Запуск подпрограммы
     *   $stat->wrapSubProgram(function () use ($stat){
     *      //Подпрограмма ничего не знает о родительском статусе выполнения, поэтому ведет собственный расчет от 0 до {%total%}
     *      //Необходимо обязательно задавать общее количество, иначе {%commonPercent%} == {%percent%} == 0%
     *      $stat->setTotal(50);
     *      //{%total%} = 50
     *
     *      for ($i = 0; $i < 5; $i++) {
     *          $stat->increaseProgress(10);
     *          //$i=0  -->  {%percent%} = 20%,  {%progress%} = 10, {%commonPercent%} = 28%
     *          //$i=1  -->  {%percent%} = 40%,  {%progress%} = 20, {%commonPercent%} = 36%
     *          //$i=2  -->  {%percent%} = 60%,  {%progress%} = 30, {%commonPercent%} = 44%
     *          //$i=3  -->  {%percent%} = 80%,  {%progress%} = 40, {%commonPercent%} = 52%
     *          //$i=4  -->  {%percent%} = 100%, {%progress%} = 50, {%commonPercent%} = 60%
     *
     *          //Здесь параметры {%progress%} из {%total%} будут для текущего контекста, но {%commonPercent%}% - для общего
     *          $stat->say('Процесс 2 {%commonPercent%}% элементов ({%progress%} из {%total%})');
     *      }
     *   }, 600); //Здесь 600 - прогресс который будет достигнут после выполнения подпрограммы
     *   //{%commonPercent%} == {%percent%} == 60%, т.к. ни одна подпрограмма не была обернута
     *   //{%progress%} = 600
     *   //{%total%} = 1000
     *
     *   for ($i = 60; $i <= 100; $i += 10) {
     *      $stat->setProgress($i * 10);
     *      //$i=60  -->  {%percent%} = 60%  {%progress%} = 600,  {%commonPercent%} = 60%
     *      //$i=70  -->  {%percent%} = 70%  {%progress%} = 700,  {%commonPercent%} = 70%
     *      //$i=80  -->  {%percent%} = 80%  {%progress%} = 800,  {%commonPercent%} = 80%
     *      //$i=90  -->  {%percent%} = 90%  {%progress%} = 900,  {%commonPercent%} = 90%
     *      //$i=100 -->  {%percent%} = 100% {%progress%} = 1000, {%commonPercent%} = 100%
     *
     *      $stat->say('Процесс 1 {%percent%}% элементов ({%progress%} из {%total%})');
     *   }
     * @param \Closure $func Первым аргументом передается $this
     * @param int $endProgress прогресс после выполнения подпрограммы
     */
    public function wrapSubProgram(\Closure $func, $endProgress)
    {
        $context = [
            'total' => $this->total,
            'progress' => $this->progress,
            'end' => $endProgress,
            'lastPercent' => $this->lastPercent,
            'percent' => $this->percent,
        ];
        $this->subProcesses[] = $context;
        $this->total = 0;
        $this->progress = 0;
        $this->lastPercent = 0;
        $func($this);
        $context = array_pop($this->subProcesses);
        $this->total = $context['total'];
        $this->progress = $endProgress;
        $this->lastPercent = $context['lastPercent'];
        $this->percent = $context['percent'];
    }

    /**
     * Рассказать о текущем прогрессе выполнения
     * @param $msg
     * @return $this
     */
    public function say($msg)
    {
        if ($this->stepProcessingProgress > 0) {
            if ($this->percent > 0 &&
                ($this->percent % $this->stepProcessingProgress) < 1 &&
                (int)$this->lastPercent < (int)$this->percent) {
                $this->sayConcrete($msg);
            }
            return $this;
        }

        $this->sayConcrete($msg);
        return $this;
    }

    /**
     * Рассказать о текущем прогрессе выполнения конкретному экзкмпляру статуса
     * @param $msg
     */
    abstract protected function sayConcrete($msg);

    /**
     * Рассказать о текущем этапе выполнения
     * @param $stage
     * @return $this
     */
    abstract public function sayStage($stage);

    public function setTotal(int $total)
    {
        if ($total <= 0) {
            throw new \Exception("Общее количество должно быть положительным числом больше 0");
        }
        $this->total = $total;
        return $this;
    }

    /**
     * Получение максимального прогресса
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Получение времени последнего изменения прогресса
     * @return float
     */
    public function getTimeLast(): float
    {
        return $this->timeLast;
    }

    /**
     * Получение времени старта процесса
     * @return float
     */
    public function getTimeStart(): float
    {
        return $this->timeStart;
    }

    /**
     * Установка текущего прогресса
     * @param $progress
     * @return $this
     */
    public function setProgress($progress)
    {
        if ($this->total <= 0) {
            throw new \Exception("Необходимо заполнить обязательный параметр total");
        }
        if ($progress < 0 || $progress > $this->total) {
            throw new \Exception("Прогресс должен быть положительным числом меньше total");
        }
        $this->lastPercent = $this->percent;
        $this->percent = ($progress / $this->total) * 100;
        $this->progress = $progress;
        $this->timeLast = microtime(true);
        return $this;
    }

    /**
     * Увеличение прогресса
     * @param int $step Шаг
     * @return $this
     */
    public function increaseProgress($step = 1)
    {
        if ($step <= 0) {
            throw new \Exception("Шаг должен быть положительным числом больше 0");
        }
        $this->setProgress($this->progress + $step);
        return $this;
    }

    /**
     * Обнуление текущего прогресса
     * @return $this
     */
    public function clearProgress()
    {
        $this->setProgress(0);
        return $this;
    }

    /**
     * Текущий прогресс
     * @return mixed
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Текущий процент выполнения
     * @return float
     */
    public function getPercent()
    {
        return $this->percent;
    }

    /**
     * Расчет общего процента выполнения
     * @return float|int
     */
    public function getCommonPercent()
    {
        if ($this->total <= 0) {
            throw new \Exception("Необходимо заполнить обязательный параметр total");
        }

        $stepPercent = 1;
        $parentPercent = 0;
        foreach ($this->subProcesses as $subProcess) {
            $endPercent = $parentPercent + ($subProcess['end'] / $subProcess['total']) * 100 * $stepPercent;
            if ($subProcess['total'] > 0) {
                $parentPercent += $stepPercent * ($subProcess['progress'] / $subProcess['total']) * 100;
            }
            $stepPercent *= ($endPercent - $parentPercent) / 100;
        }

        return $parentPercent + $stepPercent * (($this->progress / $this->total) * 100);
    }
}
