<?php

namespace App\Services;

use App\Models\ImportHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    public function sendImportFailureAlert(ImportHistory $import): void
    {
        $alertData = [
            'filename' => $import->filename,
            'status' => $import->status->value,
            'error_message' => $import->error_message,
            'started_at' => $import->started_at?->toDateTimeString(),
            'finished_at' => $import->finished_at?->toDateTimeString(),
            'total_products' => $import->total_products,
            'imported_products' => $import->imported_products,
            'failed_products' => $import->failed_products,
        ];

        Log::channel('import_alerts')->error('Import process failed', $alertData);

        if (config('mail.alerts.enabled', false)) {
            $this->sendEmailAlert($alertData);
        }

        if (config('services.slack.webhook_url')) {
            $this->sendSlackAlert($alertData);
        }
    }

    public function sendBatchImportSummary(array $results): void
    {
        if ($results['failed_files'] > 0) {
            $alertData = [
                'total_files' => $results['total_files'],
                'processed_files' => $results['processed_files'],
                'failed_files' => $results['failed_files'],
                'total_products' => $results['total_products'],
                'severity' => 'warning',
            ];

            Log::channel('import_alerts')->warning('Import batch completed with failures', $alertData);
        }
    }

    private function sendEmailAlert(array $data): void
    {
        try {
            $recipients = config('mail.alerts.recipients', []);
            
            if (empty($recipients)) {
                return;
            }

            Mail::send('emails.import-failure', ['data' => $data], function ($message) use ($data) {
                $message->to(config('mail.alerts.recipients'))
                    ->subject('[ALERT] Import Failure: ' . $data['filename']);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send email alert', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    private function sendSlackAlert(array $data): void
    {
        try {
            $webhookUrl = config('services.slack.webhook_url');
            
            if (!$webhookUrl) {
                return;
            }

            $payload = [
                'text' => ':warning: *Import Failure Alert*',
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "*Import Failed*\n\nFile: `{$data['filename']}`\nStatus: `{$data['status']}`",
                        ],
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Started:*\n{$data['started_at']}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Finished:*\n{$data['finished_at']}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Total Products:*\n{$data['total_products']}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Failed:*\n{$data['failed_products']}",
                            ],
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "*Error:*\n```{$data['error_message']}```",
                        ],
                    ],
                ],
            ];

            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            Log::error('Failed to send Slack alert', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }
}
