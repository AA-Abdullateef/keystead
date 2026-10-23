<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->profile()->with(['country', 'state'])->first();

        if (! $profile) {
            return $this->error('Profile not found.', null, 404);
        }

        return $this->success('Profile.', new ProfileResource($profile));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated() + ['updated_by' => $user->id],
        );

        if ($profile->wasRecentlyCreated) {
            $profile->update(['created_by' => $user->id]);
        }

        return $this->success('Profile updated.', new ProfileResource($profile->load(['country', 'state'])));
    }
}
