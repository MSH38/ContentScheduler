<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\CustomResponseTrait;
use App\Traits\LogsActivity;

class PostController extends Controller
{
    use CustomResponseTrait, LogsActivity;

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
            $this->logActivity('viewed_post_list');
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
            $todayCount = Post::where('user_id', Auth::id())
                ->whereDate('created_at', now()->toDateString())
                ->count();

            if ($todayCount >= 10) {
                $this->logActivity('post_creation_blocked_limit');
                return $this->customResponse('You’ve reached the daily limit of 10 scheduled posts.', 429);
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
            $this->logActivity('post_created', ['post_id' => $post->id, 'title' => $post->title]);
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
            $this->logActivity('viewed_post', ['post_id' => $post->id]);
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
            $this->logActivity('post_updated', ['post_id' => $post->id]);

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
            $this->logActivity('post_deleted', ['post_id' => $postId]);
            $post->delete();
            return $this->customResponse('Post deleted successfully');
        } catch (\Exception $e) {
            return $this->customResponse($e->getMessage(), 500);
        }
    }
}
