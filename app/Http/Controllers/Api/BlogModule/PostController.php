<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    // GET /posts?page=&limit=&search=&status=&category_id=&from=&to=
    public function index(Request $request)
    {
        $query = Post::with('category');

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
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

    public function show(Post $post)
    {
        $post->load('category');
        return response()->json($post);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:draft,published',
            'featured_image' => 'nullable|image|max:5120',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = Storage::disk('public')->url($request->file('featured_image')->store('posts', 'public'));
        }
        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $post = Post::create($validated);
        $post->load('category');

        return response()->json($post, 201);
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'status' => 'sometimes|required|in:draft,published',
            'featured_image' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = Storage::disk('public')->url($request->file('featured_image')->store('posts', 'public'));
        }
        if (($validated['status'] ?? null) === 'published' && $post->status !== 'published') {
            $validated['published_at'] = now();
        }

        $post->update($validated);
        $post->load('category');

        return response()->json($post);
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return response()->json(null, 204);
    }
}
