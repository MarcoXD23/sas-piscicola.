<?php

namespace App\Http\Controllers;

use App\Models\DailyLabor;
use App\Models\FishCredit;
use App\Models\FishingAttendance;
use App\Models\FishSale;
use App\Models\PayrollSettlement;
use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeeklyPayrollController extends Controller
{
    /**
     * Historial de liquidaciones semanales de nómina.
     */
    public function index(Request $request): JsonResponse
    {
        $settlements = PayrollSettlement::query()
            ->with(['settledBy:id,name,role'])
            ->orderBy('cutoff_date', 'desc')
            ->get();

        return response()->json([
            'total_liquidaciones' => $settlements->count(),
            'data' => $settlements,
        ]);
    }

    /**
     * Automatización del cálculo de la paga acumulada de la semana:
     * - Clasificación: 'fijo' (mensual) y 'destajo_semanal' / 'temporal' (pago semanal los sábados).
     * - Asistencia a pesca: Registro los lunes (o martes si es festivo) y jornales.
     * - Excluye a trabajadores fijos del pago de sábado.
     * - Descuento de pescado fiado a $7.000 COP/kg:
     *   * Destajo semanal: se deduce directamente de la nómina del sábado.
     *   * Fijo: se acumula para el cierre mensual.
     */
    public function calculateWeeklyPayroll(Request $request): JsonResponse
    {
        $cutoffDate = $this->resolveSaturdayCutoff($request->input('cutoff_date'));
        $weekStartDate = $cutoffDate->copy()->startOfWeek()->toDateString(); // Lunes
        $saturdayCutoff = $cutoffDate->toDateString(); // Sábado de corte

        // 1. Consultar jornales tradicionales pendientes
        $labors = DailyLabor::query()
            ->pending()
            ->whereBetween('work_date', [$weekStartDate, $saturdayCutoff])
            ->with(['user', 'pond'])
            ->get();

        // 2. Consultar asistencias a pesca de la semana (Lunes / Martes festivo)
        $attendances = FishingAttendance::query()
            ->whereBetween('attendance_date', [$weekStartDate, $saturdayCutoff])
            ->where('attended', true)
            ->with('user')
            ->get();

        // Clasificar trabajadores involucrados
        $allWorkers = User::whereIn('role', [
            User::ROLE_WORKER,
            User::ROLE_TRABAJADOR,
            User::ROLE_GUARD,
        ])->get();

        $fijosSummary = collect();
        $temporalesSummary = collect();

        // A. Agrupar por trabajadores con registros en la semana
        $workerIds = $labors->pluck('user_id')->concat($attendances->pluck('user_id'))->filter()->unique();

        // También incluir trabajadores sin user_id explícito provenientes de DailyLabor
        $unlinkedLaborWorkers = $labors->whereNull('user_id')->groupBy('worker_name');

        foreach ($workerIds as $wId) {
            $userWorker = $allWorkers->firstWhere('id', $wId);
            if (! $userWorker) {
                continue;
            }

            $userLabors = $labors->where('user_id', $wId);
            $userAttendances = $attendances->where('user_id', $wId);

            $isDestajo = $userWorker->isDestajoSemanal();
            $diasTrabajados = $userLabors->count() + $userAttendances->count();

            $wageFromLabors = (float) $userLabors->sum('daily_wage');
            $wageFromAttendances = (float) $userAttendances->sum('daily_wage');
            $totalWage = round($wageFromLabors + $wageFromAttendances, 2);

            // Consultar pescado fiado pendiente ($7.000/kg)
            $fishCredits = FishCredit::query()
                ->where('user_id', $wId)
                ->whereBetween('credit_date', [$weekStartDate, $saturdayCutoff])
                ->whereIn('status', [FishCredit::STATUS_PENDIENTE, 'pendiente'])
                ->get();

            $legacySales = FishSale::query()
                ->where('customer_type', FishSale::TYPE_WORKER)
                ->whereIn('payment_method', ['descuento_nomina', 'credito', 'fiado', 'pendiente'])
                ->whereBetween('sale_date', [$weekStartDate, $saturdayCutoff])
                ->where(function ($q) use ($userWorker) {
                    $q->where('customer_name', $userWorker->name);
                    if ($userWorker->document_number) {
                        $q->orWhere('customer_name', 'like', "%{$userWorker->document_number}%");
                    }
                })
                ->get();

            $kilosFiados = round((float) ($fishCredits->sum('kilos') + $legacySales->sum('kilos_sold')), 2);
            $tarifaPescado = (float) ($userWorker->finca?->obtenerConfig('precios.pescado_empleado_kg')
                ?? auth()->user()?->finca?->obtenerConfig('precios.pescado_empleado_kg')
                ?? FishCredit::DEFAULT_PRICE_PER_KG);
            $descuentoPescado = round($kilosFiados * $tarifaPescado, 2);

            if ($isDestajo) {
                $totalNeto = max(0.0, round($totalWage - $descuentoPescado, 2));
                $temporalesSummary->push([
                    'user_id' => $userWorker->id,
                    'worker_name' => $userWorker->name,
                    'worker_id_card' => $userWorker->document_number,
                    'tipo_empleado' => $userWorker->employment_type ?: 'destajo_semanal',
                    'dias_trabajados_count' => $diasTrabajados,
                    'total_horas' => round((float) $userLabors->sum('hours_worked') + ($userAttendances->count() * 8), 2),
                    'asistencias_pesca_count' => $userAttendances->count(),
                    'acumulado_jornales' => $totalWage,
                    'paga_acumulada_total' => $totalWage,
                    'kilos_pescado_fiado' => $kilosFiados,
                    'tarifa_pescado_kilo' => $tarifaPescado,
                    'descuento_pescado_fiado' => $descuentoPescado,
                    'total_neto_a_pagar' => $totalNeto,
                    'jornales_ids' => $userLabors->pluck('id'),
                    'asistencias_ids' => $userAttendances->pluck('id'),
                    'creditos_pescado_ids' => $fishCredits->pluck('id'),
                    'ventas_fiadas_ids' => $legacySales->pluck('id'),
                ]);
            } else {
                // Empleado fijo: Excluido de liquidación sabatina; pescado fiado se acumula a saldo mensual
                $fijosSummary->push([
                    'user_id' => $userWorker->id,
                    'worker_name' => $userWorker->name,
                    'worker_id_card' => $userWorker->document_number,
                    'tipo_empleado' => 'fijo',
                    'dias_trabajados_count' => $diasTrabajados,
                    'total_horas' => round((float) $userLabors->sum('hours_worked') + ($userAttendances->count() * 8), 2),
                    'paga_acumulada_sabado' => 0.0,
                    'kilos_pescado_fiado' => $kilosFiados,
                    'descuento_pescado_fiado' => $descuentoPescado,
                    'saldo_acumulado_mes' => round((float) $userWorker->accumulated_fish_credit + $descuentoPescado, 2),
                    'excluido_de_liquidacion' => true,
                    'motivo_exclusion' => 'Empleado fijo con salario mensual independiente. Asistencia registrada para control interno.',
                    'jornales_ids' => $userLabors->pluck('id'),
                    'asistencias_ids' => $userAttendances->pluck('id'),
                    'creditos_pescado_ids' => $fishCredits->pluck('id'),
                ]);
            }
        }

        // B. Procesar jornales no vinculados a usuarios específicos
        foreach ($unlinkedLaborWorkers as $name => $workerLabors) {
            $isFijo = $workerLabors->first()->isFijo();
            $totalWage = round((float) $workerLabors->sum('daily_wage'), 2);
            $idCard = $workerLabors->first()->worker_id_card;

            if ($isFijo) {
                $fijosSummary->push([
                    'worker_name' => $name,
                    'worker_id_card' => $idCard,
                    'tipo_empleado' => 'fijo',
                    'dias_trabajados_count' => $workerLabors->count(),
                    'paga_acumulada_sabado' => 0.0,
                    'excluido_de_liquidacion' => true,
                    'motivo_exclusion' => 'Empleado fijo. Excluido del acumulado de liquidación de sábado.',
                    'jornales_ids' => $workerLabors->pluck('id'),
                ]);
            } else {
                $legacySales = FishSale::query()
                    ->where('customer_type', FishSale::TYPE_WORKER)
                    ->whereIn('payment_method', ['descuento_nomina', 'credito', 'fiado', 'pendiente'])
                    ->whereBetween('sale_date', [$weekStartDate, $saturdayCutoff])
                    ->where(function ($q) use ($name, $idCard) {
                        $q->where('customer_name', $name);
                        if ($idCard) {
                            $q->orWhere('customer_name', 'like', "%{$idCard}%");
                        }
                    })
                    ->get();

                $kilosFiados = round((float) $legacySales->sum('kilos_sold'), 2);
                $tarifaPescado = FishSale::DEFAULT_PRICE_WORKER;
                $descuentoPescado = round($kilosFiados * $tarifaPescado, 2);
                $totalNeto = max(0.0, round($totalWage - $descuentoPescado, 2));

                $temporalesSummary->push([
                    'worker_name' => $name,
                    'worker_id_card' => $idCard,
                    'tipo_empleado' => $workerLabors->first()->employment_type ?? 'temporal',
                    'dias_trabajados_count' => $workerLabors->count(),
                    'total_horas' => round((float) $workerLabors->sum('hours_worked'), 2),
                    'acumulado_jornales' => $totalWage,
                    'paga_acumulada_total' => $totalWage,
                    'kilos_pescado_fiado' => $kilosFiados,
                    'tarifa_pescado_kilo' => $tarifaPescado,
                    'descuento_pescado_fiado' => $descuentoPescado,
                    'total_neto_a_pagar' => $totalNeto,
                    'jornales_ids' => $workerLabors->pluck('id'),
                    'ventas_fiadas_ids' => $legacySales->pluck('id'),
                ]);
            }
        }

        $totalBruto = round((float) $temporalesSummary->sum('acumulado_jornales'), 2);
        $totalDescuentos = round((float) $temporalesSummary->sum('descuento_pescado_fiado'), 2);
        $totalNeto = round((float) $temporalesSummary->sum('total_neto_a_pagar'), 2);

        return response()->json([
            'message' => 'Cálculo de nómina semanal generado para corte de día sábado.',
            'periodo_nomina' => [
                'semana_inicio_lunes' => $weekStartDate,
                'fecha_corte_sabado' => $saturdayCutoff,
                'es_dia_sabado' => $cutoffDate->isSaturday(),
            ],
            'totales_nomina' => [
                'total_trabajadores' => $temporalesSummary->count(),
                'total_trabajadores_temporales' => $temporalesSummary->count(),
                'total_trabajadores_fijos_excluidos' => $fijosSummary->count(),
                'total_jornales_acumulados' => $labors->count(),
                'total_jornales_temporales' => $labors->filter(fn ($l) => $l->isTemporal())->count(),
                'total_jornales_fijos' => $labors->filter(fn ($l) => $l->isFijo())->count(),
                'total_asistencias_pesca' => $attendances->count(),
                'total_bruto_jornales' => $totalBruto,
                'total_descuento_pescado_fiado' => $totalDescuentos,
                'total_neto_a_pagar' => $totalNeto,
                'total_a_pagar' => $totalNeto > 0 || $totalDescuentos > 0 ? $totalNeto : $totalBruto,
            ],
            'nomina_personal_temporal' => $temporalesSummary->values(),
            'nomina_por_trabajador' => $temporalesSummary->values(),
            'trabajadores_fijos_excluidos' => $fijosSummary->values(),
        ]);
    }

    /**
     * Liquidar y cerrar la nómina semanal del sábado.
     * Excluye a fijos de la bolsa sabatina, acumula su pescado fiado al mes, y liquida destajo.
     */
    public function settleWeeklyPayroll(Request $request): JsonResponse
    {
        $cutoffDate = $this->resolveSaturdayCutoff($request->input('cutoff_date'));
        $weekStartDate = $cutoffDate->copy()->startOfWeek()->toDateString();
        $saturdayCutoff = $cutoffDate->toDateString();
        $user = $request->user();

        // Obtener la liquidación calculada
        $calcResponse = $this->calculateWeeklyPayroll($request);
        $calcData = $calcResponse->getData(true);

        $temporales = collect($calcData['nomina_personal_temporal'] ?? []);
        $fijos = collect($calcData['trabajadores_fijos_excluidos'] ?? []);

        if ($temporales->isEmpty() && $fijos->isEmpty()) {
            return response()->json([
                'message' => 'No existen jornadas o asistencias pendientes para liquidar en la semana con corte al '.$saturdayCutoff.'.',
            ], 422);
        }

        $workersCount = $temporales->count();
        $totalGrossAmount = round((float) $temporales->sum('acumulado_jornales'), 2);
        $totalDeductionsAmount = round((float) $temporales->sum('descuento_pescado_fiado'), 2);
        $totalNetAmount = round((float) $temporales->sum('total_neto_a_pagar'), 2);
        $totalAmount = $totalNetAmount > 0 || $totalDeductionsAmount > 0 ? $totalNetAmount : $totalGrossAmount;

        $settlement = DB::transaction(function () use (
            $weekStartDate,
            $saturdayCutoff,
            $workersCount,
            $totalGrossAmount,
            $totalDeductionsAmount,
            $totalNetAmount,
            $totalAmount,
            $temporales,
            $fijos,
            $user,
            $request
        ) {
            $settlementRecord = PayrollSettlement::create([
                'week_start_date' => $weekStartDate,
                'cutoff_date' => $saturdayCutoff,
                'settlement_date' => now()->toDateString(),
                'total_jornales_count' => $temporales->sum('dias_trabajados_count'),
                'total_workers_count' => $workersCount,
                'total_gross_amount' => $totalGrossAmount,
                'total_deductions_amount' => $totalDeductionsAmount,
                'total_net_amount' => $totalNetAmount,
                'total_amount' => $totalAmount,
                'status' => PayrollSettlement::STATUS_LIQUIDADA,
                'settled_by_user_id' => $user->id,
                'notes' => $request->input('notes'),
            ]);

            // Liquidar jornales y créditos de destajo semanal
            foreach ($temporales as $tempWorker) {
                if (! empty($tempWorker['jornales_ids'])) {
                    DailyLabor::whereIn('id', $tempWorker['jornales_ids'])->update([
                        'payment_status' => DailyLabor::STATUS_LIQUIDADO,
                        'payroll_settlement_id' => $settlementRecord->id,
                    ]);
                }

                if (! empty($tempWorker['creditos_pescado_ids'])) {
                    FishCredit::whereIn('id', $tempWorker['creditos_pescado_ids'])->update([
                        'status' => FishCredit::STATUS_DESCONTADO_SABADO,
                        'payroll_settlement_id' => $settlementRecord->id,
                    ]);
                }

                if (! empty($tempWorker['ventas_fiadas_ids'])) {
                    FishSale::whereIn('id', $tempWorker['ventas_fiadas_ids'])->update([
                        'payment_method' => 'descuento_nomina_liquidado',
                    ]);
                }
            }

            // Para trabajadores fijos: acumular pescado fiado al saldo mensual
            foreach ($fijos as $fijoWorker) {
                if (! empty($fijoWorker['jornales_ids'])) {
                    DailyLabor::whereIn('id', $fijoWorker['jornales_ids'])->update([
                        'payment_status' => DailyLabor::STATUS_LIQUIDADO,
                        'payroll_settlement_id' => $settlementRecord->id,
                    ]);
                }

                if (! empty($fijoWorker['creditos_pescado_ids'])) {
                    FishCredit::whereIn('id', $fijoWorker['creditos_pescado_ids'])->update([
                        'status' => FishCredit::STATUS_ACUMULADO_MENSUAL,
                    ]);
                }

                if (! empty($fijoWorker['user_id']) && ! empty($fijoWorker['descuento_pescado_fiado']) && $fijoWorker['descuento_pescado_fiado'] > 0) {
                    User::where('id', $fijoWorker['user_id'])->increment('accumulated_fish_credit', $fijoWorker['descuento_pescado_fiado']);
                }
            }

            return $settlementRecord;
        });

        // Notificación de Resumen Sabatino por WhatsApp al Jefe Mayor
        $jefes = User::whereIn('role', [
            User::ROLE_JEFE_MAYOR,
            User::ROLE_JEFE_FINCA,
            User::ROLE_OWNER,
            User::ROLE_JEFE,
        ])->get();

        app(WhatsAppNotificationService::class)->sendSaturdayPayrollAlert([
            'fecha_corte' => $settlement->cutoff_date ? Carbon::parse($settlement->cutoff_date)->format('d/m/Y') : now()->format('d/m/Y'),
            'total_trabajadores' => $settlement->total_workers_count,
            'total_jornales' => $settlement->total_jornales_count,
            'total_neto' => $settlement->total_net_amount ?: $settlement->total_amount,
            'total_descuento_pescado' => $settlement->total_deductions_amount,
        ], $jefes);

        return response()->json([
            'message' => 'Nómina semanal liquidada y cerrada exitosamente para el día sábado.',
            'data' => $settlement->load('settledBy:id,name'),
            'resumen_personal_temporal' => $temporales,
            'trabajadores_fijos_excluidos' => $fijos,
        ], 201);
    }

    /**
     * Exporta el historial o resumen de nómina cerrada en formato CSV/Excel descargable.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $cutoffDate = $this->resolveSaturdayCutoff($request->input('cutoff_date'));
        $saturdayCutoff = $cutoffDate->toDateString();

        $calcResponse = $this->calculateWeeklyPayroll($request);
        $calcData = $calcResponse->getData(true);

        $temporales = $calcData['nomina_personal_temporal'] ?? [];
        $fijos = $calcData['trabajadores_fijos_excluidos'] ?? [];

        $filename = "Nomina_Sabatina_{$saturdayCutoff}.csv";

        return response()->streamDownload(function () use ($temporales, $fijos, $saturdayCutoff) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para visualización perfecta en Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['EL SAS PISCICOLA - LIQUIDACION DE NOMINA SEMANAL'], ';');
            fputcsv($handle, ['Fecha de Corte:', $saturdayCutoff], ';');
            fputcsv($handle, [], ';');

            // Sección 1: Personal de Destajo Semanal (Liquidado)
            fputcsv($handle, ['PERSONAL A DESTAJO SEMANAL (PAGO SABADO)'], ';');
            fputcsv($handle, [
                'Trabajador',
                'Cedula / Doc',
                'Tipo Contrato',
                'Dias / Asistencias',
                'Total Horas',
                'Total Bruto ($)',
                'Kg Pescado Fiado',
                'Dcto Pescado ($)',
                'Neto a Pagar ($)',
                'Estado',
            ], ';');

            foreach ($temporales as $row) {
                fputcsv($handle, [
                    $row['worker_name'] ?? 'N/A',
                    $row['worker_id_card'] ?? 'N/A',
                    'Destajo Semanal',
                    $row['dias_trabajados_count'] ?? 0,
                    $row['total_horas'] ?? 0,
                    number_format($row['acumulado_jornales'] ?? 0, 2, ',', '.'),
                    $row['kilos_pescado_fiado'] ?? 0,
                    number_format($row['descuento_pescado_fiado'] ?? 0, 2, ',', '.'),
                    number_format($row['total_neto_a_pagar'] ?? 0, 2, ',', '.'),
                    'Liquidado Sabado',
                ], ';');
            }

            fputcsv($handle, [], ';');

            // Sección 2: Personal Fijo (Excluido de pago sábado / Acumulado mensual)
            fputcsv($handle, ['PERSONAL FIJO (EXCLUIDO DE PAGO SABATINO - CONTROL MENSUAL)'], ';');
            fputcsv($handle, [
                'Trabajador',
                'Cedula / Doc',
                'Tipo Contrato',
                'Dias Laborados',
                'Paga Sabado ($)',
                'Kg Pescado Fiado',
                'Pescado Acumulado Mes ($)',
                'Motivo',
            ], ';');

            foreach ($fijos as $row) {
                fputcsv($handle, [
                    $row['worker_name'] ?? 'N/A',
                    $row['worker_id_card'] ?? 'N/A',
                    'Fijo Mensual',
                    $row['dias_trabajados_count'] ?? 0,
                    '0,00',
                    $row['kilos_pescado_fiado'] ?? 0,
                    number_format($row['descuento_pescado_fiado'] ?? 0, 2, ',', '.'),
                    'Salario mensual. Pescado acumulado para fin de mes.',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Detalle de una liquidación semanal con sus jornales asociados.
     */
    public function show(PayrollSettlement $payrollSettlement): JsonResponse
    {
        $payrollSettlement->load(['settledBy:id,name', 'dailyLabors']);

        return response()->json([
            'data' => $payrollSettlement,
        ]);
    }

    /**
     * Resuelve la fecha de corte de sábado: si no se suministra, calcula el sábado de la semana en curso.
     */
    private function resolveSaturdayCutoff(?string $dateInput): Carbon
    {
        if ($dateInput) {
            return Carbon::parse($dateInput);
        }

        $now = now();

        return $now->isSaturday() ? $now : $now->next(Carbon::SATURDAY);
    }
}
