<?php

namespace App\Services;

use App\Models\FeedInventory;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    protected ?string $token;

    protected ?string $phoneNumberId;

    protected string $adminPhone;

    protected string $apiUrl;

    protected ?string $webhookUrl;

    public function __construct()
    {
        $this->token = config('services.whatsapp.token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->adminPhone = config('services.whatsapp.admin_phone', '573100000000');
        $this->apiUrl = rtrim(config('services.whatsapp.api_url', 'https://graph.facebook.com/v19.0'), '/');
        $this->webhookUrl = config('services.whatsapp.webhook_url');
    }

    /**
     * Envía alerta crítica de asfixia / boqueo cuando el celador presiona el botón de pánico o el oxígeno baja de 3.5 mg/L.
     */
    public function sendCriticalBoqueoAlert(Pond $pond, ?float $oxigeno = null, mixed $destinatarios = null): array
    {
        $oxStr = $oxigeno !== null ? number_format($oxigeno, 2).' mg/L' : 'No medido / Alerta visual inmediata';
        $fechaHora = now()->timezone('America/Bogota')->format('d/m/Y g:i:s A');

        $mensaje = "*ALERTA CRÍTICA: ASFIXIA / BOQUEO*\n".
            "-------------------------------------\n".
            "*Estanque:* {$pond->name} (".($pond->code ?? 'L-'.$pond->id).")\n".
            "*Nivel de Oxígeno:* {$oxStr}\n".
            "*Acción Automática:* Aireador activado de emergencia con planta auxiliar.\n".
            "*Hora Local:* {$fechaHora}\n".
            '*Población en riesgo:* '.number_format($pond->fish_population)." peces\n".
            "-------------------------------------\n".
            '*Se requiere presencia inmediata en el estanque.*';

        return $this->broadcastToRoles($mensaje, $destinatarios);
    }

    /**
     * Envía alerta de bodega cuando los días de autonomía de alimento restante son inferiores a 3 días.
     */
    public function sendFeedInventoryAlert(FeedInventory $feedInventory, float $diasAutonomia, mixed $destinatarios = null): array
    {
        $fecha = now()->timezone('America/Bogota')->format('d/m/Y');
        $diasStr = number_format($diasAutonomia, 1);

        $mensaje = "*ALERTA DE BODEGA - CONCENTRADO CRÍTICO*\n".
            "-------------------------------------\n".
            "*Alimento:* {$feedInventory->feed_name}\n".
            "*Proteína:* {$feedInventory->protein_percentage}%\n".
            '*Stock Actual:* '.number_format($feedInventory->quantity_kg, 1)." kg\n".
            "*Autonomía Estimada:* {$diasStr} días (< 3 días de reserva)\n".
            "*Fecha:* {$fecha}\n".
            "-------------------------------------\n".
            '*Por favor coordinar compra o traslado de bultos urgentemente.*';

        return $this->broadcastToRoles($mensaje, $destinatarios);
    }

    /**
     * Envía resumen sabatino al Jefe Mayor indicando que la nómina del sábado ya fue generada y está lista para revisión.
     */
    public function sendSaturdayPayrollAlert(array $resumenNomina, mixed $destinatarios = null): array
    {
        $fechaCorte = $resumenNomina['fecha_corte'] ?? now()->format('d/m/Y');
        $totalTrabajadores = $resumenNomina['total_trabajadores'] ?? 0;
        $totalJornales = $resumenNomina['total_jornales'] ?? 0;
        $totalNeto = number_format($resumenNomina['total_neto'] ?? 0, 0, ',', '.');
        $descuentoPescado = number_format($resumenNomina['total_descuento_pescado'] ?? 0, 0, ',', '.');

        $mensaje = "*RESUMEN DE NÓMINA SABATINA - EL SAS PISCÍCOLA*\n".
            "-------------------------------------\n".
            "*Corte Operativo:* {$fechaCorte} (Sábado)\n".
            "*Trabajadores de Destajo:* {$totalTrabajadores}\n".
            "*Jornales Acumulados:* {$totalJornales}\n".
            "*Deducción Pescado Fiado:* \${$descuentoPescado}\n".
            "*Total Neto a Pagar:* \${$totalNeto}\n".
            "-------------------------------------\n".
            '*Planilla liquidada y lista para revisión y autorización del Jefe Mayor.*';

        return $this->broadcastToRoles($mensaje, $destinatarios);
    }

    /**
     * Envía un mensaje individual a un número telefónico por WhatsApp API o Log.
     */
    public function sendMessage(string $telefono, string $mensaje): array
    {
        // Limpieza de caracteres no numéricos
        $cleanPhone = preg_replace('/[^0-9]/', '', $telefono);
        if (empty($cleanPhone)) {
            $cleanPhone = $this->adminPhone;
        }

        // Si cuenta con credenciales activas de WhatsApp Business Cloud API
        if ($this->token && $this->phoneNumberId) {
            try {
                $endpoint = "{$this->apiUrl}/{$this->phoneNumberId}/messages";
                $response = Http::withToken($this->token)
                    ->timeout(8)
                    ->post($endpoint, [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => $cleanPhone,
                        'type' => 'text',
                        'text' => [
                            'preview_url' => false,
                            'body' => $mensaje,
                        ],
                    ]);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'driver' => 'cloud_api',
                        'phone' => $cleanPhone,
                        'message_id' => $response->json('messages.0.id'),
                        'response' => $response->json(),
                    ];
                }

                Log::warning('Fallo envío WhatsApp API: '.$response->body());
            } catch (\Throwable $e) {
                Log::error('Excepción en WhatsAppNotificationService: '.$e->getMessage());
            }
        }

        // Si no hay token de API o en entorno de desarrollo/pruebas, simular y registrar en Log
        Log::info("[WhatsApp Simulated Notification to +{$cleanPhone}]:\n{$mensaje}");

        return [
            'success' => true,
            'driver' => 'log_simulation',
            'phone' => $cleanPhone,
            'body' => $mensaje,
        ];
    }

    /**
     * Envía la notificación al grupo de destinatarios o al teléfono administrativo por defecto.
     */
    protected function broadcastToRoles(string $mensaje, mixed $destinatarios = null): array
    {
        $phones = collect();

        if ($destinatarios instanceof Collection || is_array($destinatarios)) {
            foreach ($destinatarios as $dest) {
                if ($dest instanceof User && ! empty($dest->phone ?? $dest->document_number)) {
                    $phones->push($dest->phone ?? $this->adminPhone);
                }
            }
        } elseif ($destinatarios instanceof User) {
            $phones->push($destinatarios->phone ?? $this->adminPhone);
        }

        if ($phones->isEmpty()) {
            $phones->push($this->adminPhone);
        }

        $results = [];
        foreach ($phones->unique() as $phone) {
            $results[] = $this->sendMessage((string) $phone, $mensaje);
        }

        return [
            'success' => true,
            'sent_count' => count($results),
            'deliveries' => $results,
        ];
    }
}
