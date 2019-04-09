ProgressStatus
======

Caution!

    This is the documentation for ProgressStatus 0.x.

ProgressStatus это простой в использовании компонент для расчета общего процента 
выполнения задачи многомодульных программ на PHP.

Installation
------------

Before using ProgressStatus in your project, run this script:

.. code-block:: bash

    $ composer require den1008/progress-status

Usage
-----

На данный момент есть 3 способа вывода общего прогресса: **StdOutStatus**, **FileStatus** и **StubStatus**, но вы можете написать и свои по аналогии.

Базовые методы (AbstractStatus)
~~~~~~~~~~~~~~~~~

Для того чтобы уведомить пользователя о изменении прогресса выполнения есть два метода *say()* или *sayStage()* 

.. code-block:: php

  $status->say();      //Вывод сообщений об изменении прогресса
  $status->sayStage(); //Вывод сообщений об изменении этапа выполнения

Замечание! Реализация метода *sayStage()* делегируется наследникам базового класса, а метод *say()* реализуется непосредственно в AbstractStatus. Это сделано для того, чтобы иметь возможность отсекать часть сообщений для наследников AbstractStatus. Пример: 

.. code-block:: php
   
    $status = new SomeStatus();
    $status->setTotal(100)
           ->setStepProcessingProgress(25); //Теперь все наследники будут получать сообщения с шагом 25%
    for ($i = 0; $i < 100; $i++){
        $status->increaseProgress()->say('processing');
    }

Вывод **SomeStatus**

.. code-block:: bash

   [I completed 25%] processing
   [I completed 50%] processing
   [I completed 75%] processing
   [I completed 100%] processing
   
Подсчет статистики во вложенных подпрограммах с помощью метода wrapSubProgram(\Closure $func, $endProgress),
где $func - это запускаемая подпрограмма, а $endProgress - прогресс, который будет достигнут по завершению подпрограммы.
Пример использования:

.. code-block:: php

    $stat = new SomeStatus();
    $stat->setTotal(1000);
      //{%total%} = 1000
      for ($i = 0; $i <= 20; $i += 10) {
         $stat->setProgress($i * 10);
         //$i=0  -->  {%percent%} = 0%,  {%progress%} = 0,   {%commonPercent%} = 0%
         //$i=10 -->  {%percent%} = 10%, {%progress%} = 100, {%commonPercent%} = 10%
         //$i=20 -->  {%percent%} = 20%, {%progress%} = 200, {%commonPercent%} = 20%

         $stat->say('Процесс 1 {%percent%}% элементов ({%progress%} из {%total%})');
      }
      //Запуск подпрограммы
      $stat->wrapSubProgram(function () use ($stat){
         //Подпрограмма ничего не знает о родительском статусе выполнения, поэтому ведет собственный расчет от 0 до {%total%}
         //Необходимо обязательно задавать общее количество, иначе {%commonPercent%} == {%percent%} == 0%
         $stat->setTotal(50);
         //{%total%} = 50

         for ($i = 0; $i < 5; $i++) {
             $stat->increaseProgress(10);
             //$i=0  -->  {%percent%} = 20%,  {%progress%} = 10, {%commonPercent%} = 28%
             //$i=1  -->  {%percent%} = 40%,  {%progress%} = 20, {%commonPercent%} = 36%
             //$i=2  -->  {%percent%} = 60%,  {%progress%} = 30, {%commonPercent%} = 44%
             //$i=3  -->  {%percent%} = 80%,  {%progress%} = 40, {%commonPercent%} = 52%
             //$i=4  -->  {%percent%} = 100%, {%progress%} = 50, {%commonPercent%} = 60%

             //Здесь параметры {%progress%} из {%total%} будут для текущего контекста, но {%commonPercent%}% - для общего
             $stat->say('Процесс 2 {%commonPercent%}% элементов ({%progress%} из {%total%})');
         }
      }, 600); //Здесь 600 - прогресс который будет достигнут после выполнения подпрограммы
      //{%commonPercent%} == {%percent%} == 60%, т.к. ни одна подпрограмма не была обернута
      //{%progress%} = 600
      //{%total%} = 1000

      for ($i = 60; $i <= 100; $i += 10) {
         $stat->setProgress($i * 10);
         //$i=60  -->  {%percent%} = 60%  {%progress%} = 600,  {%commonPercent%} = 60%
         //$i=70  -->  {%percent%} = 70%  {%progress%} = 700,  {%commonPercent%} = 70%
         //$i=80  -->  {%percent%} = 80%  {%progress%} = 800,  {%commonPercent%} = 80%
         //$i=90  -->  {%percent%} = 90%  {%progress%} = 900,  {%commonPercent%} = 90%
         //$i=100 -->  {%percent%} = 100% {%progress%} = 1000, {%commonPercent%} = 100%

         $stat->say('Процесс 1 {%percent%}% элементов ({%progress%} из {%total%})');
      }

StdOutStatus
~~~~~~~~~~~~~~~~~

Данный компонент выводит информацию о прогрессе в std. 
Простой пример использования выглядит следующим образом:

.. code-block:: php

    use \den1008\ProgressStatus\StdOutStatus;
    
    // define status
    $status = new StdOutStatus();
    $status->setTotal(100);
    for ($i = 0; $i < 100; $i++){
        $status->increaseProgress()->say('processing');
    }
При выполнении скрипта в консоль будут выведены следующие строки:

.. code-block:: bash

   1
   2
   3

Для того чтобы не печатать каждый раз новую строку, необходимо сделать так:

.. code-block:: php

    $status = new StdOutStatus(0); // изменение глубины вывода вложенных прогрессов

При таком подходе в консоле будет выводится одна строка, которая будет изменяться при вызове методов *say()* или *sayStage()*.

Для изменения шаблонов вывода сообщений необходимо изменить публичные поля класса:

.. code-block:: php  

   /** @var string Формат вывода */
    public $mainFormat = "[{%date%} {%time%}][{%commonPercent%}%][{%totalTime%}] {%msg%}";
    /** @var string Формат вывода скрытых однострочных статусов */
    public $hideFormat = "[{%date%} {%time%}][{%commonPercent%}%][{%totalTime%}] └ {%msg%}";
    
Доступные параметры для вывода
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

Для установления максимальной длины сообщения необходимо вызвать метод *setMaxStrLength()*, например

.. code-block:: php  

  $status = (new StdOutStatus(0))->setMaxStrLength(120); // сообщения длиной больше 120 символов будут обрезаны при выводе


Test
~~~~~~~~~~~~~~~~~

Используется PhpUnit

.. code-block:: bash

  php vendor/bin/phpunit tests/
