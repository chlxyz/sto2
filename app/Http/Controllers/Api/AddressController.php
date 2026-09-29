<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Address::where('user_id', $request->user()->id)->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'sometimes|string|max:64',
            'contact_name' => 'sometimes|nullable|string|max:255',
            'address' => 'required|string|max:500',
            'address_lat' => 'sometimes|nullable|numeric|between:-90,90',
            'address_lng' => 'sometimes|nullable|numeric|between:-180,180',
        ]);
        $data['label'] = trim($data['label'] ?? '') ?: 'Home';

        return response()->json(
            $request->user()->addresses()->create($data), 201
        );
    }

    public function update(Request $request, $id)
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'label' => 'sometimes|string|max:64',
            'contact_name' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|string|max:500',
            'address_lat' => 'sometimes|nullable|numeric|between:-90,90',
            'address_lng' => 'sometimes|nullable|numeric|between:-180,180',
        ]);
        if (array_key_exists('label', $data)) {
            $data['label'] = trim($data['label']) ?: 'Home';
        }
        $address->update($data);

        return response()->json($address->fresh());
    }

    public function destroy(Request $request, $id)
    {
        Address::where('user_id', $request->user()->id)->findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
