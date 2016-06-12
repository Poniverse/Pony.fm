<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
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

use DB;
use Gravatar;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Auth;
use Illuminate\Support\Str;
use Poniverse\Ponyfm\Contracts\Commentable;
use Poniverse\Ponyfm\Contracts\Searchable;
use Poniverse\Ponyfm\Traits\IndexedInElasticsearchTrait;
use Validator;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * Poniverse\Ponyfm\Models\User
 *
 * @property integer $id
 * @property string $display_name
 * @property string $username
 * @property boolean $sync_names
 * @property string $email
 * @property string $gravatar
 * @property string $slug
 * @property boolean $uses_gravatar
 * @property boolean $can_see_explicit_content
 * @property string $bio
 * @property integer $track_count
 * @property integer $comment_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $avatar_id
 * @property string $remember_token
 * @property boolean $is_archived
 * @property \Carbon\Carbon $disabled_at
 * @property-read \Poniverse\Ponyfm\Models\Image $avatar
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\ResourceUser[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Role[] $roles
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Comment[] $comments
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Track[] $tracks
 * @property-read mixed $url
 * @property-read mixed $message_url
 * @property-read \Illuminate\Database\Eloquent\Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\User userDetails()
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Notification[] $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\User[] $followers
 * @property-read mixed $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Activity[] $activities
 */
class User extends Model implements AuthenticatableContract, CanResetPasswordContract, \Illuminate\Contracts\Auth\Access\Authorizable, Searchable, Commentable
{
    use Authenticatable, CanResetPassword, Authorizable, RevisionableTrait, IndexedInElasticsearchTrait;

    protected $elasticsearchType = 'user';

    protected $table = 'users';
    protected $casts = [
        'id'                        => 'integer',
        'sync_names'                => 'boolean',
        'uses_gravatar'             => 'boolean',
        'can_see_explicit_content'  => 'boolean',
        'track_count'               => 'integer',
        'comment_count'             => 'integer',
        'avatar_id'                 => 'integer',
        'is_archived'               => 'boolean',
    ];
    protected $dates = ['created_at', 'updated_at', 'disabled_at'];
    protected $hidden = ['disabled_at', 'remember_token'];

    public function scopeUserDetails($query)
    {
        if (Auth::check()) {
            $query->with([
                'users' => function($query) {
                    $query->whereUserId(Auth::user()->id);
                }
            ]);
        }

        return !$query;
    }

    /**
     * Takes the given string, slugifies it, and increments a counter if needed
     * to generate a unique slug version of it.
     *
     * @param string $name
     * @return string a unique slug
     */
    private static function getUniqueSlugForName(string $name):string {
        $baseSlug = Str::slug($name);

        // Ensure that the slug we generate is long enough.
        for ($i = Str::length($baseSlug); $i < config('ponyfm.user_slug_minimum_length'); $i++) {
            $baseSlug = $baseSlug.'-';
        }

        $slugBeingTried = $baseSlug;
        $counter = 2;

        while (true) {
            $existingEntity = static::where('slug', $slugBeingTried)->first();
            $validator = Validator::make(['slug' => $slugBeingTried], ['isNotReservedSlug']);

            if ($existingEntity || $validator->fails()) {
                $slugBeingTried = "{$baseSlug}-{$counter}";
                $counter++;
                continue;

            } else {
                break;
            }
        }

        return $slugBeingTried;
    }

    /**
     * @param string $username used to perform the search
     * @param string $displayName
     * @param string|null $email set to null if creating an archived user
     * @param bool $createArchivedUser if true, includes archived users in the search and creates an archived user
     * @return User
     */
    public static function findOrCreate(
        string $username,
        string $displayName,
        string $email = null,
        bool $createArchivedUser = false
    ) {
        $user = static::where(DB::raw('LOWER(username)'), Str::lower($username));
        if (false === $createArchivedUser) {
            $user = $user->where('is_archived', false);
        }
        $user = $user->first();

        if (null !== $user) {
            return $user;

        } else {
            $user = new User;

            $user->username = $username;
            $user->display_name = $displayName;
            $user->slug = self::getUniqueSlugForName($displayName);
            $user->email = $email;
            $user->uses_gravatar = true;
            $user->is_archived = $createArchivedUser;
            $user->save();
            $user = $user->fresh();
            $user->wasRecentlyCreated = true;

            return $user;
        }
    }

    public function avatar()
    {
        return $this->belongsTo(Image::class);
    }

    public function users()
    {
        return $this->hasMany(ResourceUser::class, 'artist_id');
    }
    
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'artist_id', 'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function comments():HasMany
    {
        return $this->hasMany(Comment::class, 'profile_id')->orderBy('created_at', 'desc');
    }

    public function tracks()
    {
        return $this->hasMany(Track::class, 'user_id');
    }
    
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function notificationActivities()
    {
        return $this->hasManyThrough(Activity::class, Notification::class, 'user_id', 'notification_id', 'id');
    }

    public function getIsArchivedAttribute()
    {
        return (bool) $this->attributes['is_archived'];
    }

    public function getUrlAttribute()
    {
        return action('ArtistsController@getProfile', $this->slug);
    }

    public function getMessageUrlAttribute()
    {
        return 'http://mlpforums.com/index.php?app=members&module=messaging&section=send&do=form&fromMemberID='.$this->id;
    }

    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getReminderEmail()
    {
        return $this->email;
    }

    public function setDisplayNameAttribute($value)
    {
        $this->attributes['display_name'] = $value;
    }

    public function getAvatarUrl($type = Image::NORMAL)
    {
        if (!$this->uses_gravatar) {
            return $this->avatar->getUrl($type);
        }

        if ($this->email == "redacted@example.net") {
            return Gravatar::getUrl($this->id."", Image::$ImageTypes[$type]['width'], "identicon");
        }

        $email = $this->gravatar;

        if (!strlen($email)) {
            $email = $this->email;
        }

        return Gravatar::getUrl($email, Image::$ImageTypes[$type]['width']);
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return "remember_token";
    }

    public function getUserAttribute():User {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getResourceType():string {
        return 'profile';
    }

    public function activities():MorphMany {
        return $this->morphMany(Activity::class, 'resource');
    }

    /**
     * Returns true if this user has the given role.
     *
     * @param string $roleName
     * @return bool
     */
    public function hasRole($roleName):bool
    {
        foreach ($this->roles as $role) {
            if ($role->name === $roleName) {
                return true;
            }
        }

        return false;
    }

    public static function mapPublicUserSummary(User $user) {
        return [
            'id' => $user->id,
            'name' => $user->display_name,
            'slug' => $user->slug,
            'url' => $user->url,
            'is_archived' => $user->is_archived,
            'avatars' => [
                'small' => $user->getAvatarUrl(Image::SMALL),
                'normal' => $user->getAvatarUrl(Image::NORMAL)
            ],
            'created_at' => $user->created_at
        ];
    }

    /**
     * Returns this model in Elasticsearch-friendly form. The array returned by
     * this method should match the current mapping for this model's ES type.
     *
     * @return array
     */
    public function toElasticsearch():array {
        return [
            'username'      => $this->username,
            'display_name'  => $this->display_name,
            'tracks'        => $this->tracks->pluck('title'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function shouldBeIndexed():bool {
        return $this->disabled_at === null;
    }
}
