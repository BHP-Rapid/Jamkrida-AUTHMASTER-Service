<?php

namespace App\Jobs;

use App\Models\NotifMitra;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendBulkNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $title;

    protected string $message;

    protected string $type;

    protected array $target;

    public function __construct(string $title, string $message, string $type, array $target = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->target = $target;
    }

    public function handle(): void
    {
        try {
            Log::info("Start Notif Mitra: {$this->title}, Recipient Type: {$this->type}");

            $target = [];

            if ($this->type === 'all') {
                $target = DB::table('user_mitra')
                    ->where('role', 'mitra')
                    ->orderBy('name')
                    ->pluck('user_id')
                    ->toArray();
            } else {
                $target = $this->target;
            }

            foreach ($target as $recipient) {
                Log::info("Notif Mitra: {$recipient}");

                NotifMitra::query()->create([
                    'mitra_user_id' => $recipient,
                    'title' => $this->title,
                    'message' => $this->message,
                ]);
            }
        } catch (Exception $exception) {
            Log::error('Send bulk notification failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'title' => $this->title,
                'type' => $this->type,
                'target' => $this->target,
            ]);
        }
    }
}
