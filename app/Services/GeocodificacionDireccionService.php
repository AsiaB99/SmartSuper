<?php

namespace App\Services;

use Throwable;
use Illuminate\Support\Facades\Http;

class GeocodificacionDireccionService
{
    /**
     * @return array{latitud: float, longitud: float}|null
     */
    public function buscarCoordenadas(string $direccion): ?array
    {
        try {
            $response = Http::acceptJson()
                ->withUserAgent((string) config('services.geocodificacion.user_agent', 'SmartSuper/1.0'))
                ->timeout(10)
                ->get((string) config('services.geocodificacion.url', 'https://nominatim.openstreetmap.org/search'), [
                    'q' => $direccion,
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => config('services.geocodificacion.countrycodes', 'es'),
                    'addressdetails' => 0,
                ]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $resultado = $response->json('0');

        if (! is_array($resultado) || ! is_numeric($resultado['lat'] ?? null) || ! is_numeric($resultado['lon'] ?? null)) {
            return null;
        }

        return [
            'latitud' => (float) $resultado['lat'],
            'longitud' => (float) $resultado['lon'],
        ];
    }
}
