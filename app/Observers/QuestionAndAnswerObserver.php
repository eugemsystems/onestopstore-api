<?php

namespace App\Observers;

use App\Models\QuestionAndAnswer;
use App\Services\ApiCacheRefresher;
use Illuminate\Support\Facades\DB;

class QuestionAndAnswerObserver
{
    public function created(QuestionAndAnswer $qa): void
    {
        $this->refresh($qa);
    }

    public function updated(QuestionAndAnswer $qa): void
    {
        $this->refresh($qa);
    }

    public function deleted(QuestionAndAnswer $qa): void
    {
        $this->refresh($qa);
    }

    public function restored(QuestionAndAnswer $qa): void
    {
        $this->refresh($qa);
    }

    public function forceDeleted(QuestionAndAnswer $qa): void
    {
        $this->refresh($qa);
    }

    protected function refresh(QuestionAndAnswer $qa): void
    {
        DB::afterCommit(function () use ($qa) {
            if ($qa->product_id) {
                ApiCacheRefresher::refreshQnaForProduct((int) $qa->product_id);
            }
        });
    }
}
