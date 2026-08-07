<?php

namespace App\Notifications;

use App\Models\CurationReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportHandled extends Notification
{
    use Queueable;

    public function __construct(
        private CurationReport $report,
        private string $resultAction,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $curation = $this->report->curation;
        $resultLabel = match ($this->resultAction) {
            'removed' => '삭제되었습니다',
            'kept' => '유지되었습니다',
            default => '처리되었습니다',
        };

        return [
            'type' => 'report_handled',
            'message' => "신고하신 리스트 \"{$curation->title}\"이(가) 검토 후 {$resultLabel}.",
            'curation_id' => $curation->id,
            'result' => $this->resultAction,
        ];
    }
}
