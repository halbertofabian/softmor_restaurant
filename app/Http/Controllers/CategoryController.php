<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PreparationArea;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('preparationArea')->get();
        return view('categories.index', compact('categories'));
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
