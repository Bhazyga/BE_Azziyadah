<?php

namespace App\Services\Midtrans;

use Midtrans\Transaction;
use Illuminate\Support\Facades\Log;

class SafeNotification
{
    protected $payload;
    protected $statusResponse;

    public function __construct()
    {
        $raw = file_get_contents("php://input");
        $this->payload = json_decode($raw, true);

        if (!isset($this->payload['transaction_id'])) {
            throw new \Exception('Missing transaction_id in notification payload');
        }

        try {
            $this->statusResponse = Transaction::status($this->payload['transaction_id']);
        } catch (\Exception $e) {
            Log::error("Failed to get transaction status from Midtrans: " . $e->getMessage());
            throw $e;
        }
    }

    public function __get($key)
    {
        return $this->statusResponse->$key ?? null;
    }

    public function getRawPayload()
    {
        return $this->payload;
    }

    public function getStatusResponse()
    {
        return $this->statusResponse;
    }
}
