<?php

namespace Phoenix\Bundle\JobQueueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * This controller largley copies BCC/ResqueBundle/Controller/DefaultController.php
 * This custom version is in place to allow an alternative template to be loaded
 */
class QueueViewController extends Controller
{
    public function indexAction()
    {
        $this->getResque()->pruneDeadWorkers();

        return $this->render(
            'PhoenixJobQueueBundle:View:index.html.twig',
            array(
                'resque' => $this->getResque(),
            )
        );
    }

    public function showQueueAction($queue)
    {
        list($start, $count, $showingAll) = $this->getShowParameters();

        $queue = $this->getResque()->getQueue($queue);
        $jobs = $queue->getJobs($start, $count);

        if (!$showingAll) {
            $jobs = array_reverse($jobs);
        }

        return $this->render(
            'PhoenixJobQueueBundle:View:queue_show.html.twig',
            array(
                'queue' => $queue,
                'jobs' => $jobs,
                'showingAll' => $showingAll,
                'resque' => $this->getResque()
            )
        );
    }

    public function listFailedAction()
    {
        list($start, $count, $showingAll) = $this->getShowParameters();

        $jobs = $this->getResque()->getFailedJobs($start, $count);

        if (!$showingAll) {
            $jobs = array_reverse($jobs);
        }

        return $this->render(
            'PhoenixJobQueueBundle:View:failed_list.html.twig',
            array(
                'jobs' => $jobs,
                'showingAll' => $showingAll,
                'resque' => $this->getResque()
            )
        );
    }

    public function listScheduledAction()
    {
        return $this->render(
            'PhoenixJobQueueBundle:View:scheduled_list.html.twig',
            array(
                'timestamps' => $this->getResque()->getDelayedJobTimestamps(),
                'resque' => $this->getResque()
            )
        );
    }

    public function showTimestampAction($timestamp)
    {
        $jobs = array();

        // we don't want to enable the twig debug extension for this...
        foreach ($this->getResque()->getJobsForTimestamp($timestamp) as $job) {
            $jobs[] = print_r($job, true);
        }

        return $this->render(
            'PhoenixJobQueueBundle:View:scheduled_timestamp.html.twig',
            array(
                'timestamp' => $timestamp,
                'jobs' => $jobs,
                'resque' => $this->getResque()
            )
        );
    }

    /**
     * @return \BCC\ResqueBundle\Resque
     */
    protected function getResque()
    {
        return $this->get('bcc_resque.resque');
    }

    /**
     * decide which parts of a job queue to show
     *
     * @return array
     */
    private function getShowParameters()
    {
        $showingAll = false;
        $start = -100;
        $count = -1;

        if ($this->getRequest()->query->has('all')) {
            $start = 0;
            $count = -1;
            $showingAll = true;
        }

        return array($start, $count, $showingAll);
    }
}
