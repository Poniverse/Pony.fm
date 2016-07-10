<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Poniverse\Ponyfm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Poniverse\Ponyfm\Models\Activity
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property integer $user_id
 * @property boolean $activity_type
 * @property boolean $resource_type
 * @property integer $resource_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Notification[] $notifications
 * @property-read \Poniverse\Ponyfm\Models\User $initiatingUser
 * @property-read \Poniverse\Ponyfm\Models\Activity $resource
 * @property-read mixed $url
 * @property-read mixed $thumbnail_url
 * @property-read mixed $text
 */
class Activity extends Model {
    use SoftDeletes;

    public $timestamps = false;
    protected $dates = ['created_at'];
    protected $fillable = ['created_at', 'user_id', 'activity_type', 'resource_type', 'resource_id'];
    protected $appends = ['url', 'thumbnail_url', 'human_friendly_resource_type'];
    protected $casts = [
        'id'            => 'integer',
        'created_at'    => 'datetime',
        'user_id'       => 'integer',
        'activity_type' => 'integer',
        // resource_type has its own accessor and mutator
        'resource_id'   => 'integer',
    ];

    const TYPE_NEWS = 1;
    const TYPE_PUBLISHED_TRACK = 2;
    const TYPE_PUBLISHED_ALBUM = 3;
    const TYPE_PUBLISHED_PLAYLIST = 4;
    const TYPE_NEW_FOLLOWER = 5;
    const TYPE_NEW_COMMENT = 6;
    const TYPE_CONTENT_FAVOURITED = 7;

    /**
     * These "target" constants are an implementation detail of this model and
     * should not be used directly in other classes. They're used to efficiently
     * store the type of resource this notification is about in the database.
     *
     * The "resource_type" attribute is transformed into a class name at runtime
     * so that the use of an integer in the database to represent this info
     * remains an implementation detail of this model. Outside of this class,
     * the resource_type attribute should be treated as a fully-qualified class
     * name.
     */
    const TARGET_USER = 1;
    const TARGET_TRACK = 2;
    const TARGET_ALBUM = 3;
    const TARGET_PLAYLIST = 4;
    const TARGET_COMMENT = 5;

    public function initiatingUser() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function notifications() {
        return $this->hasMany(Notification::class, 'activity_id', 'id');
    }
    
    public function notificationRecipients() {
        return $this->hasManyThrough(User::class, Notification::class, 'activity_id', 'user_id', 'id');
    }

    public function resource() {
        return $this->morphTo('resource', 'resource_type', 'resource_id');
    }

    public function getUrlAttribute() {
        return $this->resource->url;
    }
    
    public function getResourceTypeAttribute($value) {
        switch ($value) {
            case static::TARGET_USER:
                return User::class;

            case static::TARGET_TRACK:
                return Track::class;

            case static::TARGET_ALBUM:
                return Album::class;

            case static::TARGET_PLAYLIST:
                return Playlist::class;

            case static::TARGET_COMMENT:
                return Comment::class;
            
            default:
                // Null must be returned here for Eloquent's eager-loading
                // of the polymorphic relation to work.
                return NULL;
                
        }
    }

    public function setResourceTypeAttribute($value) {
        switch ($value) {
            case User::class:
                $this->attributes['resource_type'] = static::TARGET_USER;
                break;

            case Track::class:
                $this->attributes['resource_type'] = static::TARGET_TRACK;
                break;

            case Album::class:
                $this->attributes['resource_type'] = static::TARGET_ALBUM;
                break;

            case Playlist::class:
                $this->attributes['resource_type'] = static::TARGET_PLAYLIST;
                break;

            case Comment::class:
                $this->attributes['resource_type'] = static::TARGET_COMMENT;
                break;
        }
    }

    public function getThumbnailUrlAttribute()
    {
        switch ($this->resource_type) {
            case User::class:
                return $this->resource->getAvatarUrl(Image::THUMBNAIL);

            case Track::class:
            case Album::class:
            case Playlist::class:
                return $this->resource->getCoverUrl(Image::THUMBNAIL);

            case Comment::class:
                return $this->resource->user->getAvatarUrl(Image::THUMBNAIL);

            default:
                throw new \Exception('This activity\'s resource is of an unknown type!');
        }
    }

    public function getTitleFromActivityType() {
        switch($this->activity_type) {
            case static::TYPE_PUBLISHED_TRACK:
                return "Pony.fm - New track";
            case static::TYPE_PUBLISHED_PLAYLIST:
                return "Pony.fm - New playlist";
            case static::TYPE_NEW_FOLLOWER:
                return "Pony.fm - New follower";
            case static::TYPE_NEW_COMMENT:
                return "Pony.fm - New comment";
            case static::TYPE_CONTENT_FAVOURITED:
                return "Pony.fm - Favourited";

            default:
                return "Pony.fm - Unknown";
        }
    }

    /**
     * @return string human-readable Markdown string describing this notification
     * @throws \Exception
     */
    public function getTextAttribute()
    {
        switch ($this->activity_type) {
            case static::TYPE_NEWS:
                // not implemented yet
                throw new \InvalidArgumentException('This type of activity has not been implemented yet!');

            case static::TYPE_PUBLISHED_TRACK:
                return "{$this->resource->user->display_name} published a new track, {$this->resource->title}!";

            case static::TYPE_PUBLISHED_PLAYLIST:
                return "{$this->resource->user->display_name} published a new playlist, {$this->resource->title}!";

            case static::TYPE_NEW_FOLLOWER:
                return "{$this->initiatingUser->display_name} is now following you!";

            case static::TYPE_NEW_COMMENT:
                // Is this a profile comment?
                if ($this->resource_type === User::class) {
                    return "{$this->initiatingUser->display_name} left a comment on your profile!";

                // Must be a content comment.
                } else {
                    return "{$this->initiatingUser->display_name} left a comment on your {$this->resource->resource->getResourceType()}, {$this->resource->resource->title}!";
                }
            
            case static::TYPE_CONTENT_FAVOURITED:
                return "{$this->initiatingUser->display_name} favourited your {$this->resource->type}, __{$this->resource->title}__!";

            default:
                throw new \Exception('This activity\'s activity type is unknown!');
        }
    }
}
