<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{

    use SoftDeletes;

    protected $table = 'comments';

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo('User');
    }

    public function track()
    {
        return $this->belongsTo('Track');
    }

    public function album()
    {
        return $this->belongsTo('Album');
    }

    public function playlist()
    {
        return $this->belongsTo('Playlist');
    }

    public function profile()
    {
        return $this->belongsTo('User', 'profile_id');
    }

    public static function mapPublic($comment)
    {
        return [
            'id' => $comment->id,
            'created_at' => $comment->created_at,
            'content' => $comment->content,
            'user' => [
                'name' => $comment->user->display_name,
                'id' => $comment->user->id,
                'url' => $comment->user->url,
                'avatars' => [
                    'normal' => $comment->user->getAvatarUrl(Image::NORMAL),
                    'thumbnail' => $comment->user->getAvatarUrl(Image::THUMBNAIL),
                    'small' => $comment->user->getAvatarUrl(Image::SMALL),
                ]
            ]
        ];
    }

    public function getResourceAttribute()
    {
        if ($this->track_id !== null) {
            return $this->track;
        } else {
            if ($this->album_id !== null) {
                return $this->album;
            } else {
                if ($this->playlist_id !== null) {
                    return $this->playlist;
                } else {
                    if ($this->profile_id !== null) {
                        return $this->profile;
                    } else {
                        return null;
                    }
                }
            }
        }
    }
}