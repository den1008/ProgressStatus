<?php

namespace den1008\ProgressStatus\tests;

use den1008\ProgressStatus\classes\AbstractStatus;
use PHPUnit\Framework\TestCase;

/**
 * Class ProcessStatusTest
 *
 * @package tests
 */
class ProcessStatusTest extends TestCase
{
    protected function getInstance(): TestProcessStatus
    {
        return new TestProcessStatus();
    }

    /**
     * Тестирование вложенного подсчета прогресса
     * @throws \Exception
     */
    public function testWrappingAndProgress()
    {
        $status = $this->getInstance()->setTotal(1000);
        $this->assertEquals(0, $status->getNested());

        $lastCommonPercent = 0;
        $funcCheckCommonPercent = function (AbstractStatus $status) use (
            &
            $lastCommonPercent
        ) {
            $this->assertGreaterThanOrEqual($lastCommonPercent,
                $status->getCommonPercent());
            $lastCommonPercent = $status->getCommonPercent();
        };

        $funcCheckCommonPercent($status);
        $status->wrapSubProgram(function (AbstractStatus $status) use (
            $funcCheckCommonPercent
        ) {
            $status->setTotal(500);
            $funcCheckCommonPercent($status);
            $this->assertEquals(1, $status->getNested());
            for ($i = 1; $i <= 100; $i++) {
                $status->increaseProgress();
                $funcCheckCommonPercent($status);
                $this->assertEquals($i, $status->getProgress());
                $status->wrapSubProgram(function (AbstractStatus $status) use (
                    $funcCheckCommonPercent
                ) {
                    $status->setTotal(30);
                    $funcCheckCommonPercent($status);
                    $this->assertEquals(2, $status->getNested());
                    for ($i = 1; $i <= 30; $i++) {
                        $status->setProgress($i);
                        $funcCheckCommonPercent($status);
                        $this->assertEquals($i, $status->getProgress());
                    }
                    $this->assertEquals(30, $status->getTotal());
                }, $i);
                $funcCheckCommonPercent($status);
                $this->assertEquals(1, $status->getNested());
                $this->assertEquals(500, $status->getTotal());
                $this->assertEquals($i, $status->getProgress());
            }
        }, 800);
        $funcCheckCommonPercent($status);
        $this->assertEquals(0, $status->getNested());
        $this->assertEquals(1000, $status->getTotal());
        $this->assertEquals(800, $status->getProgress());

    }

    /**
     * Тестирование установки валидных значений
     * @throws \Exception
     */
    public function testSetValidValues(){
        $status = $this->getInstance()->setTotal(234)->setProgress(0);
        $this->assertEquals(234, $status->getTotal());
        $this->assertEquals(0, $status->getProgress());
        $this->getInstance()->setTotal(234)->setProgress(234);
        $status->setStepProcessingProgress(0);
        $status->setStepProcessingProgress(34);
        $status->setStepProcessingProgress(100);
    }

    /**
     * Тестирование установки невалидных значений
     * @throws \Exception
     */
    public function testSetInvalidTotalAndProgress()
    {
        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance();
            //Ошибка, отрицательный максимальный прогресс
            $status->setTotal(-1);
        });

        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance();
            //Ошибка, 0 максимальный прогресс
            $status->setTotal(0);
        });

        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance();
            //Ошибка, не указан максимальный прогресс
            $status->getCommonPercent();
        });

        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance();
            //Ошибка, не указан максимальный прогресс
            $status->setProgress(3);
        });

        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance();
            //Ошибка, не указан максимальный прогресс
            $status->increaseProgress();
        });

        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance();
            //Ошибка, не указан максимальный прогресс
            $status->clearProgress();
        });

        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance()->setTotal(100);
            //Ошибка, указан отрицательный шаг
            $status->increaseProgress(-1);
        });

        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance()->setTotal(100);
            //Ошибка, указан отрицательный прогресс
            $status->setProgress(-1);
        });

        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance()->setTotal(100);
            //Ошибка, прогресс не может превосходить максимальное значение
            $status->setProgress(101);
        });

        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance()->setTotal(100);
            //Ошибка, шаг обработки сообщений должен быть в диапазоне от 0 до 100
            $status->setStepProcessingProgress(101);
        });

        $this->expectThrowable(\Exception::class, function () {
            $status = $this->getInstance()->setTotal(100);
            //Ошибка, шаг обработки сообщений должен быть в диапазоне от 0 до 100
            $status->setStepProcessingProgress(-1);
        });
    }

    /**
     * Тестирование сброса прогресса выполнения
     * @throws \Exception
     */
    public function testClearProgress()
    {
        $status = $this->getInstance()->setTotal(1000)->setProgress(59);

        $status->clearProgress();
        $this->assertEquals(0, $status->getProgress());
        $this->assertEquals(0, $status->getPercent());
    }

    /**
     * Тестирование правильности подсчета процента выполнения
     * @throws \Exception
     */
    public function testPercent()
    {
        $status = $this->getInstance()->setTotal(50);

        for ($i = 1; $i <= 10; $i++) {
            $status->increaseProgress();
            $this->assertEquals($i * 2, $status->getPercent());
        }

        $status->wrapSubProgram(function (AbstractStatus $status) {
            $status->setTotal(10);
            for ($i = 1; $i <= 10; $i++) {
                $status->increaseProgress();
                $this->assertEquals($i * 10, $status->getPercent());
            }
        }, 50);
    }

    /**
     * Тестирование шага с которым обрабатываются сообщения попавшие в метод say()
     * @throws \Exception
     */
    public function testStepProcessingProgress()
    {
        $status = $this->getInstance()->setTotal(50);
        for ($i = 1; $i <= 50; $i++) {
            $status->increaseProgress()->say("E-хо-хо");
        }
        $this->assertEquals(50, $status->countSay);


        $status = $this->getInstance()->setTotal(50);
        $status->setStepProcessingProgress(10);
        for ($i = 1; $i <= 50; $i++) {
            $status->increaseProgress()->say("E-хо-хо");
        }
        $this->assertEquals(10, $status->countSay);
    }

    /**
     * Тестирование правильности подсчета времени выполнения скрипта
     * @throws \Exception
     */
    public function testTime()
    {
        $status = $this->getInstance()->setTotal(10);

        $lastTime = $status->getTimeStart();
        for ($i = 1; $i <= 10; $i++) {
            usleep(500);
            $status->increaseProgress();
            $this->assertTrue($status->getTimeStart() < $status->getTimeLast());
            $this->assertTrue($lastTime < $status->getTimeLast());
            $lastTime = $status->getTimeLast();
        }

        $status = $this->getInstance()->setTotal(10);
        for ($i = 1; $i <= 10; $i++) {
            usleep(500);
            $status->setProgress($i);

            $this->assertTrue($status->getTimeStart() < $status->getTimeLast());
            $this->assertTrue($lastTime < $status->getTimeLast());
            $lastTime = $status->getTimeLast();
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
