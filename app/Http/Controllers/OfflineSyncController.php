<?php

namespace App\Http\Controllers;

use App\Models\FeedingLog;
use App\Models\InventarioAlimento;
use App\Models\OfflineSyncLog;
use App\Models\Pond;
use App\Models\PondSampling;
use App\Models\RegistroCalidadAgua;
use App\Models\RegistroMortalidad;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineSyncController extends Controller
{
    /**
     * Endpoint central de sincronización offline por lotes (POST /api/v1/sync).
     * Procesa registros diferidos generados por la PWA o app móvil en zonas sin cobertura celular.
     */
    public function batchSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*.client_uuid' => ['required', 'string', 'max:64'],
            'records.*.table_name' => ['required', 'string', 'in:alimentacion,mortalidad,calidad_agua,muestreo'],
            'records.*.action' => ['required', 'string', 'in:create,update'],
            'records.*.device_timestamp' => ['required', 'date'],
            'records.*.payload' => ['required', 'array'],
        ]);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        $processedUuids = [];
        $ignoredLwwUuids = [];
        $failedUuids = [];

        foreach ($validated['records'] as $record) {
            $clientUuid = $record['client_uuid'];
            $tableName = $record['table_name'];
            $action = $record['action'];
            $deviceTimestamp = Carbon::parse($record['device_timestamp']);
            $payload = $record['payload'];

            // 1. Idempotencia: Verificar si el UUID del cliente ya fue procesado con anterioridad
            $existingLog = OfflineSyncLog::where('client_uuid', $clientUuid)->first();
            if ($existingLog) {
                $processedUuids[] = [
                    'client_uuid' => $clientUuid,
                    'status' => 'already_processed',
                    'original_processed_at' => $existingLog->processed_at->toIso8601String(),
                ];

                continue;
            }

            try {
                DB::beginTransaction();

                $syncedStatus = OfflineSyncLog::STATUS_SYNCED;

                // 2. Procesamiento según tabla y resolución de conflictos LWW
                switch ($tableName) {
                    case 'alimentacion':
                        $pond = Pond::where('finca_id', $fincaId)->findOrFail($payload['pond_id']);
                        $amountKg = (float) ($payload['amount_kg'] ?? $payload['cantidad_kg'] ?? 0);
                        $fecha = isset($payload['feeding_date']) ? Carbon::parse($payload['feeding_date']) : $deviceTimestamp->toDateString();

                        // Descontar inventario de bodega
                        $alimento = InventarioAlimento::where('finca_id', $fincaId)->first();
                        if ($alimento && $alimento->stock_actual_kg >= $amountKg) {
                            $alimento->decrement('stock_actual_kg', $amountKg);
                        }

                        FeedingLog::create([
                            'finca_id' => $fincaId,
                            'pond_id' => $pond->id,
                            'user_id' => $user?->id ?? 1,
                            'feeding_date' => $fecha,
                            'amount_kg' => $amountKg,
                            'feed_name' => $payload['feed_name'] ?? $alimento?->tipo_concentrado ?? 'Concentrado 32%',
                            'observations' => ($payload['observations'] ?? '').' [Sync Offline LWW]',
                        ]);
                        break;

                    case 'mortalidad':
                        $pond = Pond::where('finca_id', $fincaId)->lockForUpdate()->findOrFail($payload['estanque_id']);
                        $cantidadPeces = (int) $payload['cantidad_peces'];
                        $fecha = isset($payload['fecha']) ? Carbon::parse($payload['fecha']) : $deviceTimestamp->toDateString();

                        RegistroMortalidad::create([
                            'finca_id' => $fincaId,
                            'estanque_id' => $pond->id,
                            'user_id' => $user?->id ?? 1,
                            'fecha' => $fecha,
                            'cantidad_peces' => $cantidadPeces,
                            'causa_probable' => $payload['causa_probable'] ?? 'asfixia',
                            'metodo_disposicion' => $payload['metodo_disposicion'] ?? 'compostaje',
                            'observaciones' => ($payload['observaciones'] ?? '').' [Sync Offline LWW]',
                        ]);

                        // Restar de población y recalcular biomasa
                        $pond->fish_population = max(0, ((int) $pond->fish_population) - $cantidadPeces);
                        if (! empty($pond->fingerlings_stocked) && $pond->fingerlings_stocked >= $cantidadPeces) {
                            $pond->fingerlings_stocked -= $cantidadPeces;
                        }
                        $pond->updateBiomass();
                        break;

                    case 'calidad_agua':
                        RegistroCalidadAgua::create([
                            'finca_id' => $fincaId,
                            'estanque_id' => $payload['estanque_id'],
                            'user_id' => $user?->id ?? 1,
                            'fecha' => $payload['fecha'] ?? $deviceTimestamp->toDateString(),
                            'hora' => $payload['hora'] ?? $deviceTimestamp->format('h:i A'),
                            'oxigeno_mg_l' => (float) ($payload['oxigeno_mg_l'] ?? 5.0),
                            'temperatura_c' => (float) ($payload['temperatura_c'] ?? 27.5),
                            'ph' => (float) ($payload['ph'] ?? 7.2),
                            'disco_secchi_cm' => isset($payload['disco_secchi_cm']) ? (float) $payload['disco_secchi_cm'] : null,
                            'observaciones' => ($payload['observaciones'] ?? '').' [Sync Offline]',
                        ]);
                        break;

                    case 'muestreo':
                        $pond = Pond::where('finca_id', $fincaId)->lockForUpdate()->findOrFail($payload['pond_id']);
                        $sampledCount = (int) $payload['sampled_fish_count'];
                        $weightKg = (float) $payload['sample_total_weight_kg'];
                        $averageWeight = $sampledCount > 0 ? round(($weightKg * 1000) / $sampledCount, 2) : (float) $pond->average_weight;

                        // Estrategia Last-Write-Wins: Solo actualizar el peso promedio del estanque si el timestamp del dispositivo es más reciente
                        if ($pond->updated_at && $deviceTimestamp->lessThan($pond->updated_at)) {
                            $syncedStatus = OfflineSyncLog::STATUS_IGNORED_LWW;
                            $ignoredLwwUuids[] = $clientUuid;
                        } else {
                            $pond->average_weight = $averageWeight;
                            $pond->updateBiomass();
                        }

                        PondSampling::create([
                            'finca_id' => $fincaId,
                            'pond_id' => $pond->id,
                            'registered_by_user_id' => $user?->id ?? 1,
                            'sampling_date' => $payload['sampling_date'] ?? $deviceTimestamp->toDateString(),
                            'sampled_fish_count' => $sampledCount,
                            'sample_total_weight_kg' => $weightKg,
                            'average_weight_g' => $averageWeight,
                            'notes' => ($payload['notes'] ?? '').' [Sync Offline LWW]',
                        ]);
                        break;
                }

                // 3. Registrar auditoría e idempotencia en la base de datos
                OfflineSyncLog::create([
                    'finca_id' => $fincaId,
                    'user_id' => $user?->id ?? 1,
                    'client_uuid' => $clientUuid,
                    'table_name' => $tableName,
                    'action' => $action,
                    'device_timestamp' => $deviceTimestamp,
                    'processed_at' => now(),
                    'status' => $syncedStatus,
                    'payload' => $payload,
                ]);

                DB::commit();

                $processedUuids[] = [
                    'client_uuid' => $clientUuid,
                    'status' => $syncedStatus,
                    'table' => $tableName,
                ];
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Fallo al sincronizar registro offline {$clientUuid}: ".$e->getMessage());

                $failedUuids[] = [
                    'client_uuid' => $clientUuid,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Lote de sincronización offline procesado exitosamente.',
            'server_timestamp' => now()->toIso8601String(),
            'total_recibidos' => count($validated['records']),
            'total_procesados' => count($processedUuids),
            'total_conflictos_lww_ignorados' => count($ignoredLwwUuids),
            'total_fallidos' => count($failedUuids),
            'resumen' => [
                'procesados' => $processedUuids,
                'conflictos_lww' => $ignoredLwwUuids,
                'fallidos' => $failedUuids,
            ],
        ]);
    }
}
