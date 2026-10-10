<?php

namespace App\Policies;

use App\Models\StockBatch;
use App\Models\User;

class StockBatchPolicy
{
    public function view(User $user, StockBatch $batch): bool
    {
        return $user->isAdministrator();
    }
}
