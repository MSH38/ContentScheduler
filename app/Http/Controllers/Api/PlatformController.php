<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\CustomResponseTrait;
use App\Traits\LogsActivity;

class PlatformController extends Controller
{
    use CustomResponseTrait, LogsActivity;

    /**
     * Get all available platforms.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $platforms = Platform::get()->paginate(10);
            $this->logActivity('viewed_platform_list');
            return $this->customResponse($platforms);
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }

    /**
     * Toggle active platforms for the authenticated user.
     * Pass array of platform_ids to set.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleUserPlatforms(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'platforms'   => 'required|array',
                'platforms.*' => 'exists:platforms,id',
            ]);

            if ($validator->fails()) {
                return $this->customResponse($validator->errors(), 422);
            }

            $user = Auth::user();
            $user->activePlatforms()->sync($request->platforms); // pivot: user_platform
            $this->logActivity('toggled_user_platforms', [
                'platform_ids' => $request->platforms
            ]);

            return $this->customResponse('User platforms updated successfully');
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }

    /**
     * Show a single platform.
     *
     * @param Platform $platform
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Platform $platform)
    {
        try {
            $this->logActivity('viewed_platform', [
                'platform_id' => $platform->id
            ]);

            return $this->customResponse($platform);
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update an existing platform (admin usage).
     *
     * @param Request $request
     * @param Platform $platform
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Platform $platform)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'type' => 'required|in:twitter,instagram,linkedin,facebook,tiktok',
            ]);

            if ($validator->fails()) {
                return $this->customResponse($validator->errors(), 422);
            }

            $platform->update($request->only(['name', 'type']));
            $this->logActivity('platform_updated', [
                'platform_id' => $platform->id,
                'name' => $platform->name
            ]);
            return $this->customResponse($platform);
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete a platform (admin usage).
     *
     * @param Platform $platform
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Platform $platform)
    {
        try {
            $platform->delete();
            $this->logActivity('platform_deleted', ['platform_id' => $id]);
            return $this->customResponse('Platform deleted successfully');
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }
}
