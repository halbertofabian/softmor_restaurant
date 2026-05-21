<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PreparationArea;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index');
    }

    public function datatable()
    {
        $categories = Category::with('preparationArea')
        ->orderBy('id', 'desc')
        ->get();

        $data = $categories->map(function ($category) {
            $editUrl = route('categories.edit', $category);
            $destroyUrl = route('categories.destroy', $category);
            $token = csrf_token();

            $statusBadge = $category->status
                ? '<span class="badge bg-label-success">Activo</span>'
                : '<span class="badge bg-label-secondary">Inactivo</span>';

            $actions = '<a href="' . $editUrl . '" class="btn btn-sm btn-icon btn-text-secondary"><i class="ti tabler-edit"></i></a>'
                . '<form action="' . $destroyUrl . '" method="POST" class="d-inline">'
                . '<input type="hidden" name="_token" value="' . $token . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="btn btn-sm btn-icon btn-text-danger" data-gf-confirm="auto" data-gf-entity="la categoria" data-gf-name="' . e($category->name) . '"><i class="ti tabler-trash"></i></button>'
                . '</form>';

            return [
                'name' => e($category->name),
                'preparation_area' => e($category->preparationArea->name ?? '-'),
                'status' => $statusBadge,
                'actions' => $actions,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function create()
    {
        $preparationAreas = PreparationArea::where('status', true)->get();
        return view('categories.create', compact('preparationAreas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'preparation_area_id' => 'nullable|exists:preparation_areas,id',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status');

        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'Categoría creada con éxito.');
    }

    public function edit(Category $category)
    {
        $preparationAreas = PreparationArea::where('status', true)->get();
        return view('categories.edit', compact('category', 'preparationAreas'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required',
            'preparation_area_id' => 'nullable|exists:preparation_areas,id',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status');

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Categoría actualizada con éxito.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Categoría eliminada con éxito.');
    }
}
