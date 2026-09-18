<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Magazine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class MagazineController extends Controller
{
    // GET /magazines?page=&limit=&search=&from=&to=
    public function index(Request $request)
    {
        $query = Magazine::query();

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $totalRecords = $query->count();
        $limit = max(1, (int) $request->query('limit', 15));
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $limit;

        $data = $query->orderByDesc('id')->skip($offset)->take($limit)->get();

        return response()->json(['data' => $data, 'totalRecords' => $totalRecords]);
    }

    public function show(Magazine $magazine)
    {
        return response()->json($magazine);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'intro' => 'nullable|string',
            'issue_month' => 'nullable|string|max:20',
            'issue_year' => 'nullable|string|max:4',
            'status' => 'required|in:draft,published',
            'cover_image' => 'nullable|image|max:5120',
            'pdf_file' => 'nullable|mimes:pdf|max:20480',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = Storage::disk('public')->url($request->file('cover_image')->store('magazines', 'public'));
        }
        if ($request->hasFile('pdf_file')) {
            $validated['pdf_file'] = Storage::disk('public')->url($request->file('pdf_file')->store('magazines', 'public'));
        }
        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $magazine = Magazine::create($validated);

        return response()->json($magazine, 201);
    }

    public function update(Request $request, Magazine $magazine)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'intro' => 'nullable|string',
            'issue_month' => 'nullable|string|max:20',
            'issue_year' => 'nullable|string|max:4',
            'status' => 'sometimes|required|in:draft,published',
            'cover_image' => 'nullable|image|max:5120',
            'pdf_file' => 'nullable|mimes:pdf|max:20480',
        ]);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = Storage::disk('public')->url($request->file('cover_image')->store('magazines', 'public'));
        }
        if ($request->hasFile('pdf_file')) {
            $validated['pdf_file'] = Storage::disk('public')->url($request->file('pdf_file')->store('magazines', 'public'));
        }
        if (($validated['status'] ?? null) === 'published' && $magazine->status !== 'published') {
            $validated['published_at'] = now();
        }

        $magazine->update($validated);

        return response()->json($magazine);
    }

    public function destroy(Magazine $magazine)
    {
        $magazine->delete();
        return response()->json(null, 204);
    }
}
