<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserType;
use Illuminate\Http\Request;

class UserTypeController extends Controller
{
    // GET /user-types — no pagination needed, this list stays small
    public function index()
    {
        return response()->json(['data' => UserType::orderBy('name')->get()]);
    }

    public function show(UserType $userType)
    {
        return response()->json($userType);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:user_types,name',
            'description' => 'nullable|string|max:255',
        ]);

        $userType = UserType::create($validated);

        return response()->json($userType, 201);
    }

    public function update(Request $request, UserType $userType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:user_types,name,' . $userType->id,
            'description' => 'nullable|string|max:255',
        ]);

        $userType->update($validated);

        return response()->json($userType);
    }

    public function destroy(UserType $userType)
    {
        $userType->delete();
        return response()->json(null, 204);
    }
}
