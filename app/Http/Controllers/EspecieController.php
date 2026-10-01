<?php

namespace App\Http\Controllers;

use App\Models\Especie;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EspecieController extends Controller
{
    /**
     * Muestra la cuadrícula de la Enciclopedia / Guía de Especies Piscícolas.
     */
    public function index(Request $request): View
    {
        $query = Especie::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nombre_comun', 'like', "%{$search}%")
                    ->orWhere('nombre_cientifico', 'like', "%{$search}%")
                    ->orWhere('familia', 'like', "%{$search}%");
            });
        }

        if ($request->filled('clima')) {
            $clima = mb_strtolower(trim((string) $request->input('clima')));
            if (str_contains($clima, 'calid') || str_contains($clima, 'cálid')) {
                $query->where('clima', 'cálido');
            } elseif (str_contains($clima, 'fri') || str_contains($clima, 'frí')) {
                $query->where('clima', 'frío');
            }
        }

        $especies = $query->orderBy('id', 'asc')->get();

        $viewName = view()->exists('guia.index') ? 'guia.index' : 'especies.index';

        return view($viewName, [
            'especies' => $especies,
            'currentSearch' => $request->input('search', ''),
            'currentClima' => $request->input('clima', ''),
        ]);
    }

    /**
     * Muestra la ficha técnica detallada de una especie específica.
     */
    public function show(Especie $especie): View
    {
        $relacionadas = Especie::where('id', '!=', $especie->id)
            ->where('clima', $especie->clima)
            ->take(3)
            ->get();

        return view('especies.show', [
            'especie' => $especie,
            'relacionadas' => $relacionadas,
        ]);
    }
}
