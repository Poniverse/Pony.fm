<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class News extends Model
{
    public static function getNews($start = 0, $end = 4)
    {
        $feed = new SimplePie();
        $feed->cache = false;
        $feed->set_feed_url('http://mlpforums.com/blog/rss/404-ponyfm-development-blog/');
        $feed->init();
        $feed->handle_content_type();

        $posts = $feed->get_items($start, $end);
        $postHashes = [];

        foreach ($posts as $post) {
            $postHashes[] = self::calculateHash($post->get_permalink());
        }

        if (count($postHashes) == 0) {
            return [];
        }

        $seenRecords = Auth::check() ? self::where('user_id', '=', Auth::user()->id)->whereIn('post_hash',
            $postHashes)->get() : [];
        $seenHashes = [];

        foreach ($seenRecords as $record) {
            $seenHashes[$record->post_hash] = 1;
        }

        $postsReturn = [];

        // This date is around when the last blog post was posted as of now.
        // I put in a cutoff so that blog posts made before this update is pushed are always marked as 'read'
        $readCutoffDate = mktime(null, null, null, 4, 28, 2013);

        foreach ($posts as $post) {
            $autoRead = $post->get_date('U') < $readCutoffDate;
            $postsReturn[] = [
                'title' => $post->get_title(),
                'date' => $post->get_date('j F Y g:i a'),
                'url' => $post->get_permalink(),
                'read' => $autoRead || isset($seenHashes[self::calculateHash($post->get_permalink())])
            ];
        }

        return $postsReturn;
    }

    public static function markPostAsRead($postUrl)
    {
        $postHash = self::calculateHash($postUrl);
        $news = new News();
        $news->user_id = Auth::user()->id;
        $news->post_hash = $postHash;
        $news->save();
    }

    private static function calculateHash($postPermalink)
    {
        return md5($postPermalink);
    }
}