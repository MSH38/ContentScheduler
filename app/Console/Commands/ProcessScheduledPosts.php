<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;
use Carbon\Carbon;
class ProcessScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scheduled posts whose time has arrived';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $posts = Post::with('platforms')
        ->where('status', 'scheduled')
        ->where('scheduled_time', '<=', Carbon::now())
        ->get();

        foreach ($posts as $post) {
            $post->update(['status' => 'published']);
            $post->platforms()->updateExistingPivot($post->platforms->pluck('id'), ['platform_status' => 'published']);
            $this->info("Published post ID: {$post->id}");
        }

        return 0;
    }
}
