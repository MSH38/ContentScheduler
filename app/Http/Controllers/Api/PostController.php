<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Platform;
use Illuminate\Http\Request;
use App\Traits\CustomResponseTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    use CustomResponseTrait;

    /**
     * Get a list of posts for the authenticated user, with optional filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Post::with('platforms')
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc');

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date')) {
                $query->whereDate('scheduled_time', $request->date);
            }

            $posts = $query->paginate(10);
            return $this->customResponse($posts);
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created post with associated platforms.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title'          => 'required|string|max:255',
                'content'        => 'required|string',
                'image_url'      => 'nullable|url',
                'scheduled_time' => 'nullable|date|after_or_equal:now',
                'status'         => 'required|in:draft,scheduled,published',
                'platforms'      => 'required|array',
                'platforms.*'    => 'exists:platforms,id',
            ]);

            if ($validator->fails()) {
                return $this->customResponse($validator->errors(), 422);
            }

            $post = Post::create([
                'title'          => $request->title,
                'content'        => $request->content,
                'image_url'      => $request->image_url,
                'scheduled_time' => $request->scheduled_time,
                'status'         => $request->status,
                'user_id'        => Auth::id(),
            ]);

            $platforms = collect($request->platforms)
                ->mapWithKeys(fn($id) => [$id => ['platform_status' => 'pending']]);

            $post->platforms()->attach($platforms);

            return $this->customResponse($post->load('platforms'), 201);
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }

    /**
     * Show a specific post with its platforms.
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Post $post)
    {
        try {
            if ($post->user_id !== Auth::id()) {
                return $this->customResponse('Unauthorized', 403);
            }

            return $this->customResponse($post->load('platforms'));
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified post and re-sync platforms if provided.
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Post $post)
    {
        try {
            if ($post->user_id !== Auth::id()) {
                return $this->customResponse('Unauthorized', 403);
            }

            $validator = Validator::make($request->all(), [
                'title'          => 'sometimes|string|max:255',
                'content'        => 'sometimes|string',
                'image_url'      => 'nullable|url',
                'scheduled_time' => 'nullable|date|after_or_equal:now',
                'status'         => 'in:draft,scheduled,published',
                'platforms'      => 'array',
                'platforms.*'    => 'exists:platforms,id',
            ]);

            if ($validator->fails()) {
                return $this->customResponse($validator->errors(), 422);
            }

            $post->update($request->except('platforms'));

            if ($request->has('platforms')) {
                $platforms = collect($request->platforms)
                    ->mapWithKeys(fn($id) => [$id => ['platform_status' => 'pending']]);

                $post->platforms()->sync($platforms);
            }

            return $this->customResponse($post->load('platforms'));
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete the specified post.
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Post $post)
    {
        try {
            if ($post->user_id !== Auth::id()) {
                return $this->customResponse('Unauthorized', 403);
            }

            $post->delete();
            return $this->customResponse('Post deleted successfully');
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }
}
