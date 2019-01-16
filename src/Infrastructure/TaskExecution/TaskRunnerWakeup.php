<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use Logeecom\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use Logeecom\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Logeecom\Infrastructure\Utility\GuidProvider;
use Logeecom\Infrastructure\Utility\TimeProvider;

/**
 * Class TaskRunnerWakeup.
 *
 * @package Logeecom\Infrastructure\TaskExecution
 */
class TaskRunnerWakeup implements TaskRunnerWakeupInterface
{
    /**
     * Service instance.
     *
     * @var AsyncProcessStarterService
     */
    private $asyncProcessStarter;
    /**
     * Service instance.
     *
     * @var TaskRunnerStatusStorage
     */
    private $runnerStatusStorage;
    /**
     * Service instance.
     *
     * @var TimeProvider
     */
    private $timeProvider;
    /**
     * Service instance.
     *
     * @var GuidProvider
     */
    private $guidProvider;

    /**
     * Wakes up @see TaskRunner instance asynchronously if active instance is not already running.
     */
    public function wakeup()
    {
        try {
            $this->doWakeup();
        } catch (TaskRunnerStatusChangeException $ex) {
            Logger::logDebug(
                'Fail to wakeup task runner. Runner status storage failed to set new active state.',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                )
            );
        } catch (TaskRunnerStatusStorageUnavailableException $ex) {
            Logger::logDebug(
                'Fail to wakeup task runner. Runner status storage unavailable.',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                )
            );
        } catch (\Exception $ex) {
            Logger::logDebug(
                'Fail to wakeup task runner. Unexpected error occurred.',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                )
            );
        }
    }

    /**
     * Executes wakeup of queued task.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    private function doWakeup()
    {
        $runnerStatus = $this->getRunnerStorage()->getStatus();
        $currentGuid = $runnerStatus->getGuid();
        if (!empty($currentGuid) && !$runnerStatus->isExpired()) {
            return;
        }

        if ($runnerStatus->isExpired()) {
            $this->runnerStatusStorage->setStatus(TaskRunnerStatus::createNullStatus());
            Logger::logDebug('Expired task runner detected, wakeup component will start new instance.');
        }

        $guid = $this->getGuidProvider()->generateGuid();

        $this->runnerStatusStorage->setStatus(
            new TaskRunnerStatus(
                $guid,
                $this->getTimeProvider()->getCurrentLocalTime()->getTimestamp()
            )
        );

        $this->getAsyncProcessStarter()->start(new TaskRunnerStarter($guid));
    }

    /**
     * Gets instance of @see TaskRunnerStatusStorageInterface.
     *
     * @return TaskRunnerStatusStorageInterface Service instance.
     */
    private function getRunnerStorage()
    {
        if ($this->runnerStatusStorage === null) {
            $this->runnerStatusStorage = ServiceRegister::getService(TaskRunnerStatusStorageInterface::CLASS_NAME);
        }

        return $this->runnerStatusStorage;
    }

    /**
     * Gets instance of @see GuidProvider.
     *
     * @return GuidProvider Service instance.
     */
    private function getGuidProvider()
    {
        if ($this->guidProvider === null) {
            $this->guidProvider = ServiceRegister::getService(GuidProvider::CLASS_NAME);
        }

        return $this->guidProvider;
    }

    /**
     * Gets instance of @see TimeProvider.
     *
     * @return TimeProvider Service instance.
     */
    private function getTimeProvider()
    {
        if ($this->timeProvider === null) {
            $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        }

        return $this->timeProvider;
    }

    /**
     * Gets instance of @see AsyncProcessStarter.
     *
     * @return AsyncProcessStarterService Service instance.
     */
    private function getAsyncProcessStarter()
    {
        if ($this->asyncProcessStarter === null) {
            $this->asyncProcessStarter = ServiceRegister::getService(AsyncProcessStarter::CLASS_NAME);
        }

        return $this->asyncProcessStarter;
    }
}
