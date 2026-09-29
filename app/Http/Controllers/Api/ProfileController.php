<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'display_name' => ['sometimes', 'nullable', 'string', 'max:32', Rule::unique('users')->ignore($user->id)],
            'full_name' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|nullable|string|max:500',
            'address_lat' => 'sometimes|nullable|numeric|between:-90,90',
            'address_lng' => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        $user->update($data);

        return response()->json($user->fresh()->makeHidden('password'));
    }

    public function suggestions(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:32']);

        $base = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $data['name'])) ?: 'shopper';
        $taken = fn ($n) => \App\Models\User::where('display_name', $n)->exists();

        $out = [];
        if (! $taken($base)) {
            $out[] = $base;
        }
        for ($i = 1; count($out) < 4 && $i < 100; $i++) {
            foreach (["{$base}{$i}", "{$base}_{$i}", "{$base}.{$i}"] as $candidate) {
                if (count($out) >= 4) {
                    break;
                }
                if (! $taken($candidate)) {
                    $out[] = $candidate;
                }
            }
        }

        return response()->json($out);
    }
}
