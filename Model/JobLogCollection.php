<?php

namespace Markup\JobQueueBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A collection of job logs, includes methods for providing a 'health' for the given collection
 * based on how many have successfully completed
 */
class JobLogCollection extends ArrayCollection
{
    /**
     * Returns a value between 0 and 1 representing the ratio of completed jobs in this collection
     * that completed successfully (vs failing)
     * @return float
     */
    public function getHealthRatio()
    {
        $total = $this->count();
        if ($total === 0) {
            return 0;
        }
        $completed = 0;

        foreach($this as $log) {
            if ($log->getStatus() !== JobLog::STATUS_FAILED){
                $completed++;
            }
        }

        if($completed === 0) {
            return 0;
        }
        return round($completed/$total, 2);
    }
}
