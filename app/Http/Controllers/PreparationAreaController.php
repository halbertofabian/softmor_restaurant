<?php

namespace App\Http\Controllers;

use App\Models\PreparationArea;
use Illuminate\Http\Request;

class PreparationAreaController extends Controller
{
    public function index()
    {
        return view('preparation-areas.index');
    }

    public function datatable()
    {
        $areas = PreparationArea::all();

        $data = $areas->map(function ($area) {
            $editUrl = route('preparation-areas.edit', $area);
            $destroyUrl = route('preparation-areas.destroy', $area);
            $token = csrf_token();

            $printTicketBadge = $area->print_ticket
                ? '<span class="badge bg-label-success">SÃ­</span>'
                : '<span class="badge bg-label-secondary">No</span>';

            $statusBadge = $area->status
                ? '<span class="badge bg-label-success">Activo</span>'
                : '<span class="badge bg-label-secondary">Inactivo</span>';

            $actions = '<a href="' . $editUrl . '" class="btn btn-sm btn-icon btn-text-secondary"><i class="ti tabler-edit"></i></a>'
                . '<form action="' . $destroyUrl . '" method="POST" class="d-inline">'
                . '<input type="hidden" name="_token" value="' . $token . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="btn btn-sm btn-icon btn-text-danger" data-gf-confirm="auto" data-gf-entity="el area de preparacion" data-gf-name="' . e($area->name) . '"><i class="ti tabler-trash"></i></button>'
                . '</form>';

            return [
                'name' => e($area->name),
                'sort_order' => (string) $area->sort_order,
                'print_ticket' => $printTicketBadge,
                'status' => $statusBadge,
                'actions' => $actions,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function create()
    {
        return view('preparation-areas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'sort_order' => 'required|integer',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status'); // Handle checkbox
        $data['print_ticket'] = $request->has('print_ticket'); // Handle checkbox
        $data['sort_order'] = $request->sort_order ?? 0;

        PreparationArea::create($data);

        return redirect()->route('preparation-areas.index')->with('success', 'Área creada con éxito.');
    }

    public function edit(PreparationArea $preparationArea)
    {
        return view('preparation-areas.edit', compact('preparationArea'));
    }

    public function update(Request $request, PreparationArea $preparationArea)
    {
        $request->validate([
            'name' => 'required',
            'sort_order' => 'required|integer',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status'); // Handle checkbox
        $data['print_ticket'] = $request->has('print_ticket'); // Handle checkbox

        $preparationArea->update($data);

        return redirect()->route('preparation-areas.index')->with('success', 'Área actualizada con éxito.');
    }

    public function destroy(PreparationArea $preparationArea)
    {
        $preparationArea->delete();
        return redirect()->route('preparation-areas.index')->with('success', 'Área eliminada con éxito.');
    }
}
