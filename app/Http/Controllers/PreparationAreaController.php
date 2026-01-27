<?php

namespace App\Http\Controllers;

use App\Models\PreparationArea;
use Illuminate\Http\Request;

class PreparationAreaController extends Controller
{
    public function index()
    {
        $areas = PreparationArea::all();
        return view('preparation-areas.index', compact('areas'));
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
