<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Contact details fetched successfully.',
            'data' => ContactSetting::first(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (ContactSetting::query()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Contact details already exist. Use the update API.',
            ], 409);
        }

        $settings = ContactSetting::create($this->validatedData($request));

        return response()->json([
            'success' => true,
            'message' => 'Contact details stored successfully.',
            'data' => $settings,
        ], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $settings = ContactSetting::first();

        if (! $settings) {
            return response()->json([
                'success' => false,
                'message' => 'Contact details do not exist. Use the store API first.',
            ], 404);
        }

        $settings->update($this->validatedData($request));

        return response()->json([
            'success' => true,
            'message' => 'Contact details updated successfully.',
            'data' => $settings->fresh(),
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'whatsapp_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
        ]);
    }
}
