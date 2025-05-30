<?php

namespace App\Traits;
use App\Models\ActivityLog;

trait LogsActivity
{

    /**
     * Log user activity.
     *
     * @param string $action
     * @param array $meta
     * @return void
     */
    public function logActivity($action, $meta = [])
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'meta' => $meta,
        ]);
    }
}
