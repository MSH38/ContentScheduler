<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Traits\CustomResponseTrait;

class AnalyticsController extends Controller
{
    use CustomResponseTrait;

    /**
     * Get user statistics including total posts, scheduled, published,
     * and posts per platform.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userStats()
    {
        $userId = Auth::id();

        $total = Post::where('user_id', $userId)->count();
        $scheduled = Post::where('user_id', $userId)->where('status', 'scheduled')->count();
        $published = Post::where('user_id', $userId)->where('status', 'published')->count();

        $platformStats = Post::where('user_id', $userId)
            ->with('platforms')
            ->get()
            ->flatMap(fn($post) => $post->platforms)
            ->groupBy('name')
            ->map(fn($group) => $group->count());

        return $this->customResponse([
            'total_posts' => $total,
            'scheduled_posts' => $scheduled,
            'published_posts' => $published,
            'posts_per_platform' => $platformStats,
        ]);
    }
}
