<?php

namespace Markup\JobQueueBundle\EventSubscriber;

use Markup\JobQueueBundle\Exception\MissingJobLogException;
use Markup\JobQueueBundle\Model\JobLog;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Uses the `uuid` option to log a console command
 *
 * If exception then will log as `failed`.
 *
 * If successful will save the status as `processed` and save the memory usage
 * Note that output cannot be captured directly here - the whole process must be wrapped and the output captured
 * and save to the log against the same uuid
 *
 */
class CompleteConsoleCommandEventSubscriber implements EventSubscriberInterface
{

    /**
     * @var JobLogRepository
     */
    private $jobLogRepository;

    /**
     * @param JobLogRepository $jobLogRepository
     */
    public function __construct(JobLogRepository $jobLogRepository)
    {
        $this->jobLogRepository = $jobLogRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::TERMINATE => [
                ['onConsoleTerminate', 10]
            ],
            ConsoleEvents::EXCEPTION => [
                ['onConsoleException', 10]
            ],
        ];
    }

    /**
     * @param ConsoleTerminateEvent $event
     * @throws MissingJobLogException
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        // following jiggerypokery can be removed in symfony 2.8+
        $event->getCommand()->mergeApplicationDefinition();
        $input = new ArgvInput();
        $input->bind($event->getCommand()->getDefinition());

        $uuid = $input->getOption('uuid');
        if (!$uuid) {
            return;
        }

        // lookup job log repository for log and create one if it doesn't exist
        $log = $this->jobLogRepository->getJobLog($uuid);
        if (!$log) {
            throw new MissingJobLogException(sprintf('No log exists for uuid: `%s`', $uuid));
        }

        if($log->getStatus() === JobLog::STATUS_RUNNING) {
            $log->setStatus(JobLog::STATUS_COMPLETE);
        }
        $log->setPeakMemoryUse($this->getPeakMemoryUse());
        $log->setCompleted((new \DateTime('now'))->format('U'));
        $this->jobLogRepository->save($log);
    }

    /**
     * @param ConsoleExceptionEvent $event
     * @throws MissingJobLogException
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        // following jiggerypokery can be removed in symfony 2.8+
        $event->getCommand()->mergeApplicationDefinition();
        $input = new ArgvInput();
        $input->bind($event->getCommand()->getDefinition());

        $uuid = $input->getOption('uuid');
        if (!$uuid) {
            return;
        }

        // lookup job log repository for log and create one if it doesn't exist
        $log = $this->jobLogRepository->getJobLog($uuid);
        if (!$log) {
            throw new MissingJobLogException(sprintf('No log exists for uuid: `%s`', $uuid));
        }

        $log->setStatus(JobLog::STATUS_FAILED);
        $this->jobLogRepository->save($log);
    }

    private function getPeakMemoryUse()
    {
        return memory_get_peak_usage(true);
    }
}
