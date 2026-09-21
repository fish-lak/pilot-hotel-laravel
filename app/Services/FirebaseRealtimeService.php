<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseRealtimeService
{
    public function syncReservation(Reservation $reservation): void
    {
        $databaseUrl = rtrim((string) config('services.firebase.database_url'), '/');
        if ($databaseUrl === '') {
            return;
        }

        $url = $databaseUrl . '/reservations/' . $reservation->getKey() . '.json';
        $auth = config('services.firebase.database_auth');
        if ($auth) {
            $url .= '?auth=' . urlencode($auth);
        }

            try {
                $response = Http::timeout(10)
                    ->acceptJson()
                    ->put($url, $reservation->load('payments', 'bookingHistory')->toArray());

                if ($response->failed()) {
                    Log::warning('Firebase reservation sync failed.', [
                        'reservation_id' => $reservation->getKey(),
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                }
            } catch (Throwable $exception) {
                Log::warning('Firebase reservation sync could not connect.', [
                    'reservation_id' => $reservation->getKey(),
                    'message' => $exception->getMessage(),
                ]);
        }
    }
}