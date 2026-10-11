<?php

namespace App\Services;

use App\Models\AllotmentCase;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendCaseAlert(AllotmentCase $case, string $subject, string $message, ?User $recipient = null): void
    {
        $message = $message."\n\nCase: ".$case->title;

        if ($recipient?->email) {
            try {
                Mail::raw($message, function ($mail) use ($recipient, $subject) {
                    $mail->to($recipient->email)->subject($subject);
                });
            } catch (\Throwable $e) {
                Log::warning('Email notification failed', ['case' => (string) $case->_id, 'error' => $e->getMessage()]);
            }
        }

        $token = config('services.telegram.bot_token');
        $chatId = $recipient?->telegram_chat_id ?: config('services.telegram.chat_id');

        if ($token && $chatId) {
            try {
                Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $subject."\n\n".$message,
                ])->throw();
            } catch (\Throwable $e) {
                Log::warning('Telegram notification failed', ['case' => (string) $case->_id, 'error' => $e->getMessage()]);
            }
        }
    }
}
