<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    // GET /categories?page=&limit=&search=
    public function index(Request $request)
    {
        $query = Category::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $totalRecords = $query->count();
        $limit = max(1, (int) $request->query('limit', 15));
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $limit;

        $data = $query->orderBy('name')->skip($offset)->take($limit)->get();

        return response()->json(['data' => $data, 'totalRecords' => $totalRecords]);
    }

    public function show(Category $category)
    {
        return response()->json($category);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:5120',
            'banner' => 'nullable|image|max:5120',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = Storage::disk('public')->url($request->file('thumbnail')->store('categories', 'public'));
        }
        if ($request->hasFile('banner')) {
            $validated['banner'] = Storage::disk('public')->url($request->file('banner')->store('categories', 'public'));
        }

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:5120',
            'banner' => 'nullable|image|max:5120',
        ]);

        if (!empty($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = Storage::disk('public')->url($request->file('thumbnail')->store('categories', 'public'));
        }
        if ($request->hasFile('banner')) {
            $validated['banner'] = Storage::disk('public')->url($request->file('banner')->store('categories', 'public'));
        }

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(null, 204);
    }
}
