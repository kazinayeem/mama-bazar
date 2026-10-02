<?php

namespace App\Console\Commands;

use App\Models\ScheduledReport;
use App\Services\ActivityLoggerService;
use App\Services\ActivityReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RunScheduledReportsCommand extends Command
{
    protected $signature = 'reports:send-scheduled {--id= : Optional specific report ID to execute immediately}';

    protected $description = 'Process and dispatch scheduled administrative activity reports';

    public function handle(): int
    {
        $specificId = $this->option('id');

        $query = ScheduledReport::where('is_active', true);
        if ($specificId) {
            $query->where('id', (int) $specificId);
        } else {
            $query->where(function ($q) {
                $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
            });
        }

        $reports = $query->get();
        if ($reports->isEmpty()) {
            $this->info('No due scheduled reports found.');

            return 0;
        }

        $this->info("Found {$reports->count()} scheduled report(s) to process.");

        foreach ($reports as $report) {
            $this->processReport($report);
        }

        return 0;
    }

    protected function processReport(ScheduledReport $report): void
    {
        $this->line("Processing report #{$report->id}: '{$report->title}'...");

        try {
            $recipients = $report->recipient_emails;
            if (empty($recipients)) {
                throw new \RuntimeException('No valid recipient emails configured for this report.');
            }

            // Determine date window based on frequency
            $from = match ($report->frequency) {
                'daily' => Carbon::now()->subDay()->startOfDay()->format('Y-m-d'),
                'monthly' => Carbon::now()->subMonth()->startOfDay()->format('Y-m-d'),
                default => Carbon::now()->subDays(7)->startOfDay()->format('Y-m-d'), // weekly
            };
            $to = Carbon::now()->format('Y-m-d');

            $filters = [
                'from' => $from,
                'to' => $to,
            ];

            // Render PDF attachment
            $pdf = ActivityReportService::generatePdf($filters, $report->report_type);
            $pdfContent = $pdf->output();

            $attachmentName = 'mama_bazar_'.$report->report_type.'_report_'.date('Ymd').'.pdf';

            // Send email
            foreach ($recipients as $recipient) {
                Mail::raw(
                    "Hello Administrator,\n\nPlease find attached the scheduled {$report->frequency} activity and audit report for Mama Bazar.\n\nReport: {$report->title}\nPeriod: {$from} to {$to}\nGenerated: ".now()->toDayDateTimeString()."\n\nRegards,\nMama Bazar Administration Intelligence",
                    function ($message) use ($recipient, $report, $pdfContent, $attachmentName) {
                        $message->to($recipient)
                            ->subject("Scheduled Activity Report: {$report->title}")
                            ->attachData($pdfContent, $attachmentName, [
                                'mime' => 'application/pdf',
                            ]);
                    }
                );
            }

            // Calculate next run timestamp
            $nextRun = match ($report->frequency) {
                'daily' => Carbon::now()->addDay()->startOfDay()->addHours(6),
                'monthly' => Carbon::now()->addMonth()->startOfDay()->addHours(6),
                default => Carbon::now()->addWeek()->startOfDay()->addHours(6),
            };

            $report->update([
                'last_run_at' => now(),
                'next_run_at' => $nextRun,
                'last_status' => 'success',
                'last_error' => null,
            ]);

            ActivityLoggerService::logSystem(
                'system.scheduled_report_sent',
                "Scheduled report #{$report->id} ('{$report->title}') successfully dispatched to ".implode(', ', $recipients),
                ['metadata' => ['report_id' => $report->id, 'recipients' => $recipients]]
            );

            $this->info("✓ Report #{$report->id} sent successfully.");
        } catch (Throwable $e) {
            $report->update([
                'last_run_at' => now(),
                'last_status' => 'failure',
                'last_error' => substr($e->getMessage(), 0, 1000),
            ]);

            ActivityLoggerService::logSystem(
                'system.scheduled_report_failed',
                "Scheduled report #{$report->id} ('{$report->title}') failed: {$e->getMessage()}",
                ['status' => 'failure', 'metadata' => ['report_id' => $report->id, 'error' => $e->getMessage()]]
            );

            $this->error("✗ Failed to process report #{$report->id}: {$e->getMessage()}");
        }
    }
}
