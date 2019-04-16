ProgressStatus
======

ProgressStatus это простой в использовании компонент для расчета общего процента 
выполнения задачи многомодульных программ на PHP.

Установка
------------

.. code-block:: bash

    $ composer require den1008/progress-status

Использование
-----
Основным элементом является StatusProcessor, который реализует функции просчета прогресса и которому необходимо передавать
хэндлеры, в зависимости от того, куда необходимо выводить информацию о прогрессе.
На данный момент есть 2 хэндлера: **StdOutStatus** и **FileStatus**, но вы можете написать и свои по аналогии.


StatusProcessor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Для того чтобы уведомить пользователя о изменении прогресса выполнения есть два метода *say()* или *sayStage()* 

.. code-block:: php

  $status->say();      //Вывод сообщений об изменении прогресса
  $status->sayStage(); //Вывод сообщений об изменении этапа выполнения

Замечание! Все хэндлеры должны реализовать интерфейс IStatusHandler (методы *sayStageConcrete()* и
*sayConcrete()*, а не методы *sayStage()* и *say()*). Это сделано для того, чтобы иметь возможность отсекать
часть сообщений для хэндлеров. Пример:

.. code-block:: php
   
    $processor = new StatusProcessor([new SomeStatusHandler()]);
    $processor->setTotal(100)
           ->setStepProcessingProgress(25); //Теперь все хэндлеры будут получать сообщения с шагом 25%
    for ($i = 0; $i < 100; $i++){
        $processor->increaseProgress()->say('processing');
    }

Вывод **SomeStatusHandler**

.. code-block:: bash

   [I completed 25%] processing
   [I completed 50%] processing
   [I completed 75%] processing
   [I completed 100%] processing
   
Подсчет статистики во вложенных подпрограммах с помощью метода wrapSubProgram(\Closure $func, $endProgress),
где $func - это запускаемая подпрограмма, а $endProgress - прогресс, который будет достигнут по завершению подпрограммы.
Пример использования:

.. code-block:: php

    $processor = new StatusProcessor([new SomeStatusHandler()]);
    //Необходимо обязательно задавать общее количество, иначе будет выброшено исключение.
    //$processor->getNested() == 1 глубина вложенности
    $processor->setTotal(1000);
      //{%total%} = 1000
      for ($i = 0; $i <= 20; $i += 10) {
         $processor->setProgress($i * 10);
         //$i=0  -->  {%percent%} = 0%,  {%progress%} = 0,   {%commonPercent%} = 0%
         //$i=10 -->  {%percent%} = 10%, {%progress%} = 100, {%commonPercent%} = 10%
         //$i=20 -->  {%percent%} = 20%, {%progress%} = 200, {%commonPercent%} = 20%

         $processor->say('Процесс 1 {%percent%}% элементов ({%progress%} из {%total%})');
      }
      //Запуск подпрограммы
      $processor->wrapSubProgram(function (StatusProcessor $processor){
         //$processor->getNested() == 2 глубина вложенности
         //Подпрограмма ничего не знает о родительском статусе выполнения, поэтому ведет собственный расчет от 0 до {%total%}
         //Необходимо обязательно задавать общее количество, иначе будет выброшено исключение.
         $processor->setTotal(50);
         //{%total%} = 50

         for ($i = 0; $i < 5; $i++) {
             $processor->increaseProgress(10);
             //$i=0  -->  {%percent%} = 20%,  {%progress%} = 10, {%commonPercent%} = 28%
             //$i=1  -->  {%percent%} = 40%,  {%progress%} = 20, {%commonPercent%} = 36%
             //$i=2  -->  {%percent%} = 60%,  {%progress%} = 30, {%commonPercent%} = 44%
             //$i=3  -->  {%percent%} = 80%,  {%progress%} = 40, {%commonPercent%} = 52%
             //$i=4  -->  {%percent%} = 100%, {%progress%} = 50, {%commonPercent%} = 60%

             //Здесь параметры {%progress%} из {%total%} будут для текущего контекста, но {%commonPercent%}% - для общего
             $processor->say('Процесс 2 {%commonPercent%}% элементов ({%progress%} из {%total%})');
         }
      }, 600); //Здесь 600 - прогресс который будет достигнут после выполнения подпрограммы
      //$processor->getNested() == 1 глубина вложенности
      //{%commonPercent%} == {%percent%} == 60%, т.к. ни одна подпрограмма не была обернута
      //{%progress%} = 600
      //{%total%} = 1000

      for ($i = 60; $i <= 100; $i += 10) {
         $processor->setProgress($i * 10);
         //$i=60  -->  {%percent%} = 60%  {%progress%} = 600,  {%commonPercent%} = 60%
         //$i=70  -->  {%percent%} = 70%  {%progress%} = 700,  {%commonPercent%} = 70%
         //$i=80  -->  {%percent%} = 80%  {%progress%} = 800,  {%commonPercent%} = 80%
         //$i=90  -->  {%percent%} = 90%  {%progress%} = 900,  {%commonPercent%} = 90%
         //$i=100 -->  {%percent%} = 100% {%progress%} = 1000, {%commonPercent%} = 100%

         $processor->say('Процесс 1 {%percent%}% элементов ({%progress%} из {%total%})');
      }

StdOutStatusHandler
~~~~~~~~~~~~~~~~~

Данный компонент выводит информацию о прогрессе в std. 
Простой пример использования выглядит следующим образом:

.. code-block:: php

    use den1008\ProgressStatus\handlers\StdOutStatusHandler;
    
    // define status
    $processor = new StatusProcessor([new StdOutStatusHandler()]);
    $processor->setTotal(100);
    for ($i = 0; $i < 100; $i++){
        $processor->increaseProgress()->say('processing');
    }

При выполнении скрипта в консоль будут выведены следующие строки:

.. code-block:: bash

    [2019-04-16 19:36:51][  1.0%][    0.00] processing
    [2019-04-16 19:36:51][  2.0%][    0.00] processing
    ...
    [2019-04-16 19:36:51][100.0%][    0.00] processing

Для того чтобы не печатать каждый раз новую строку, а изменять одну и ту же, необходимо сделать так:

.. code-block:: php

    $handler = new StdOutStatusHandler(0); // изменение глубины вывода вложенных прогрессов

По умолчанию глубина скрываемых вложенных прогрессов равна 1, т.е. при вызове методов *say()* и *sayStage()*
каждое сообщение из глубины 1 будет выведено на новой строке. Для сообщений из глубины 2 прогресс будет выводится
на одной изменяемой строке.
Общий случай: если глубину скрываемых сообщений сделать равной N, то все сообщения из глубины <= N будут выводится на новой строке,
а все сообщения из глубины > N на одной изменяемой строке.


Для изменения шаблонов вывода сообщений необходимо изменить следующие публичные поля класса:

.. code-block:: php  

   /** @var string Формат вывода */
    public $mainFormat = "[{%date%} {%time%}][{%commonPercent%}%][{%totalTime%}] {%msg%}";
    /** @var string Формат вывода скрытых однострочных статусов */
    public $hideFormat = "[{%date%} {%time%}][{%commonPercent%}%][{%totalTime%}] └ {%msg%}";
    
Для форматирования сообщений используется FormatMessageTrait. Доступные параметры для вывода:
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

FileStatusHandler
~~~~~~~~~~~~~~~~~

Данный компонент выводит информацию о прогрессе в файл.
Простой пример использования выглядит следующим образом:

.. code-block:: php

    use den1008\ProgressStatus\handlers\FileStatusHandler;

    // define status
    $processor = new StatusProcessor([new FileStatusHandler('path/to/file')]);
    $processor->setTotal(100);
    for ($i = 0; $i < 100; $i++){
        $processor->increaseProgress()->say('processing');
    }

При выполнении скрипта в файл будут записаны следующие строки:

.. code-block:: bash

    [2019-04-16 19:36:51][  1.0%][    0.00] processing
    [2019-04-16 19:36:51][  2.0%][    0.00] processing
    ...
    [2019-04-16 19:36:51][100.0%][    0.00] processing

Для изменения шаблона вывода сообщений необходимо изменить следующее поле класса:

.. code-block:: php

   /** @var string Формат вывода */
   public $format = "[{%date%} {%time%}][{%commonPercent%}] {%msg%}";

Для форматирования сообщений, как и в StdOutStatusHandler используется FormatMessageTrait (см. описание выше).

Test
~~~~~~~~~~~~~~~~~

Используется PhpUnit

.. code-block:: bash

  php vendor/bin/phpunit tests/
