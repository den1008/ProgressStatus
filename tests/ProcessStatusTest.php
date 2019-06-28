<?php

namespace den1008\ProgressStatus\tests;

use den1008\ProgressStatus\handlers\StdOutStatusHandler;
use den1008\ProgressStatus\StatusProcessor;
use den1008\ProgressStatus\tests\classes\TestProcessStatusHandler;
use PHPUnit\Framework\TestCase;

/**
 * Class ProcessStatusTest
 *
 * @package tests
 */
class ProcessStatusTest extends TestCase
{
    protected function getInstance(): StatusProcessor
    {
        return new StatusProcessor(new TestProcessStatusHandler());
    }

	/**
	 * Тестирование вложенного подсчета прогресса
	 * @throws \Exception
	 */
	public function testCreateStatusProcessor()
	{
		$p1 = new StatusProcessor(new TestProcessStatusHandler());
		$this->assertEquals(1, count($p1->getHandlers()));

		$p1 = new StatusProcessor([new TestProcessStatusHandler()]);
		$this->assertEquals(1, count($p1->getHandlers()));

		$p2 = new StatusProcessor([new TestProcessStatusHandler(), new TestProcessStatusHandler()]);
		$this->assertEquals(2, count($p2->getHandlers()));

		$p3 = new StatusProcessor();
		$this->assertEquals(0, count($p3->getHandlers()));
	}

    /**
     * Тестирование вложенного подсчета прогресса
     * @throws \Exception
     */
    public function testWrappingAndProgress()
    {
        $statusProcessor = $this->getInstance()->setTotal(1000);
        $this->assertEquals(0, $statusProcessor->getNested());

        $lastCommonPercent = 0;
        $funcCheckCommonPercent = function (StatusProcessor $statusProcessor) use (
            &
            $lastCommonPercent
        ) {
            $this->assertGreaterThanOrEqual($lastCommonPercent,
                $statusProcessor->getCommonPercent());
            $lastCommonPercent = $statusProcessor->getCommonPercent();
        };

        $funcCheckCommonPercent($statusProcessor);
        $statusProcessor->wrapSubProgram(function (StatusProcessor $statusProcessor) use (
            $funcCheckCommonPercent
        ) {
            $statusProcessor->setTotal(500);
            $funcCheckCommonPercent($statusProcessor);
            $this->assertEquals(1, $statusProcessor->getNested());
            for ($i = 1; $i <= 100; $i++) {
                $statusProcessor->increaseProgress();
                $funcCheckCommonPercent($statusProcessor);
                $this->assertEquals($i, $statusProcessor->getProgress());
                $statusProcessor->wrapSubProgram(function (StatusProcessor $statusProcessor) use (
                    $funcCheckCommonPercent
                ) {
                    $statusProcessor->setTotal(30);
                    $funcCheckCommonPercent($statusProcessor);
                    $this->assertEquals(2, $statusProcessor->getNested());
                    for ($i = 1; $i <= 30; $i++) {
                        $statusProcessor->setProgress($i);
                        $funcCheckCommonPercent($statusProcessor);
                        $this->assertEquals($i, $statusProcessor->getProgress());
                    }
                    $this->assertEquals(30, $statusProcessor->getTotal());
                }, $i);
                $funcCheckCommonPercent($statusProcessor);
                $this->assertEquals(1, $statusProcessor->getNested());
                $this->assertEquals(500, $statusProcessor->getTotal());
                $this->assertEquals($i, $statusProcessor->getProgress());
            }
        }, 800);
        $funcCheckCommonPercent($statusProcessor);
        $this->assertEquals(0, $statusProcessor->getNested());
        $this->assertEquals(1000, $statusProcessor->getTotal());
        $this->assertEquals(800, $statusProcessor->getProgress());

    }

    /**
     * Тестирование установки валидных значений
     * @throws \Exception
     */
    public function testSetValidValues(){
        $statusProcessor = $this->getInstance()->setTotal(234)->setProgress(0);
        $this->assertEquals(234, $statusProcessor->getTotal());
        $this->assertEquals(0, $statusProcessor->getProgress());
        $this->getInstance()->setTotal(234)->setProgress(234);
        $statusProcessor->setStepProcessingProgress(0);
        $statusProcessor->setStepProcessingProgress(34);
        $statusProcessor->setStepProcessingProgress(100);
    }

    /**
     * Тестирование установки невалидных значений
     * @throws \Exception
     */
    public function testSetInvalidTotalAndProgress()
    {
        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance();
            //Ошибка, обертка с нулевым исходным масимальным прогрессом
            $statusProcessor->wrapSubProgram(function (StatusProcessor $processor) {
                $processor->setTotal(100)->increaseProgress(1)->getCommonPercent();
            }, 100);
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance();
            //Ошибка, отрицательный максимальный прогресс
            $statusProcessor->setTotal(-1);
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance();
            //Ошибка, 0 максимальный прогресс
            $statusProcessor->setTotal(0);
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance();
            //Ошибка, не указан максимальный прогресс
            $statusProcessor->getCommonPercent();
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance();
            //Ошибка, не указан максимальный прогресс
            $statusProcessor->setProgress(3);
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance();
            //Ошибка, не указан максимальный прогресс
            $statusProcessor->increaseProgress();
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance();
            //Ошибка, не указан максимальный прогресс
            $statusProcessor->clearProgress();
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance()->setTotal(100);
            //Ошибка, указан отрицательный шаг
            $statusProcessor->increaseProgress(-1);
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance()->setTotal(100);
            //Ошибка, указан отрицательный прогресс
            $statusProcessor->setProgress(-1);
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance()->setTotal(100);
            //Ошибка, прогресс не может превосходить максимальное значение
            $statusProcessor->setProgress(101);
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance()->setTotal(100);
            //Ошибка, шаг обработки сообщений должен быть в диапазоне от 0 до 100
            $statusProcessor->setStepProcessingProgress(101);
        });

        $this->expectThrowable(\Exception::class, function () {
            $statusProcessor = $this->getInstance()->setTotal(100);
            //Ошибка, шаг обработки сообщений должен быть в диапазоне от 0 до 100
            $statusProcessor->setStepProcessingProgress(-1);
        });
    }

    /**
     * Тестирование сброса прогресса выполнения
     * @throws \Exception
     */
    public function testClearProgress()
    {
        $statusProcessor = $this->getInstance()->setTotal(1000)->setProgress(59);

        $statusProcessor->clearProgress();
        $this->assertEquals(0, $statusProcessor->getProgress());
        $this->assertEquals(0, $statusProcessor->getPercent());
    }

    /**
     * Тестирование правильности подсчета процента выполнения
     * @throws \Exception
     */
    public function testPercent()
    {
        $statusProcessor = $this->getInstance()->setTotal(50);

        for ($i = 1; $i <= 10; $i++) {
            $statusProcessor->increaseProgress();
            $this->assertEquals($i * 2, $statusProcessor->getPercent());
        }

        $statusProcessor->wrapSubProgram(function (StatusProcessor $statusProcessor) {
            $statusProcessor->setTotal(10);
            for ($i = 1; $i <= 10; $i++) {
                $statusProcessor->increaseProgress();
                $this->assertEquals($i * 10, $statusProcessor->getPercent());
            }
        }, 50);
    }

    /**
     * Тестирование шага с которым обрабатываются сообщения попавшие в метод say()
     * @throws \Exception
     */
    public function testStepProcessingProgress()
    {
        $statusProcessor = $this->getInstance()->setTotal(50);
        $handler = new TestProcessStatusHandler();
		$statusProcessor->addHandler($handler);
        for ($i = 1; $i <= 50; $i++) {
            $statusProcessor->increaseProgress()->say("E-хо-хо");
        }
        $this->assertEquals(50, $handler->countSay);


        $statusProcessor = $this->getInstance()->setTotal(50);
		$handler = new TestProcessStatusHandler();
		$statusProcessor->addHandler($handler);
        $statusProcessor->setStepProcessingProgress(10);
        for ($i = 1; $i <= 50; $i++) {
            $statusProcessor->increaseProgress()->say("E-хо-хо");
        }
        $this->assertEquals(10, $handler->countSay);
    }

    /**
     * Тестирование правильности подсчета времени выполнения скрипта
     * @throws \Exception
     */
    public function testTime()
    {
        $statusProcessor = $this->getInstance()->setTotal(10);

        $lastTime = $statusProcessor->getTimeStart();
        for ($i = 1; $i <= 10; $i++) {
            usleep(500);
            $statusProcessor->increaseProgress();
            $this->assertTrue($statusProcessor->getTimeStart() < $statusProcessor->getTimeLast());
            $this->assertTrue($lastTime < $statusProcessor->getTimeLast());
            $lastTime = $statusProcessor->getTimeLast();
        }

        $statusProcessor = $this->getInstance()->setTotal(10);
        for ($i = 1; $i <= 10; $i++) {
            usleep(500);
            $statusProcessor->setProgress($i);

            $this->assertTrue($statusProcessor->getTimeStart() < $statusProcessor->getTimeLast());
            $this->assertTrue($lastTime < $statusProcessor->getTimeLast());
            $lastTime = $statusProcessor->getTimeLast();
        }
    }

    protected function expectThrowable($exception, \Closure $func)
    {
        try {
            $func();
        } catch (\Exception $e) {
            if (get_class($e) != $exception) {
                $this->fail("Expected $exception exception, but raised " . get_class($e));
            }
            $this->assertTrue(true);
            return;
        }

        $this->fail("Expected $exception has not been raised.");
    }
}
