<?php

namespace App\Exceptions;

use Exception;

class PlanLimitExceededException extends Exception
{
    public function render()
    {
        return response()->json([
            'message' => $this->getMessage() ?: 'Plan limit exceeded. Please upgrade your plan.',
            'code' => 'PLAN_LIMIT',
            'upgrade_url' => '/settings/billing',
        ], 403);
    }
}
