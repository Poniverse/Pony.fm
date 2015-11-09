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

namespace Poniverse\Ponyfm;

use Exception;
use Gravatar;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Venturecraft\Revisionable\RevisionableTrait;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract, \Illuminate\Contracts\Auth\Access\Authorizable
{
    use Authenticatable, CanResetPassword, Authorizable, RevisionableTrait;

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

    public function scopeUserDetails($query)
    {
        if (Auth::check()) {
            $query->with([
                'users' => function ($query) {
                    $query->whereUserId(Auth::user()->id);
                }
            ]);
        }

        return !$query;
    }

    /**
     * @param string $username
     * @param string $displayName
     * @param string $email
     * @return User
     */
    public static function findOrCreate(string $username, string $displayName, string $email) {
        $user = static::where('username', $username)
            ->where('is_archived', false)
            ->first();

        if (null !== $user) {
            return $user;

        } else {
            $user = new User;

            $user->username = $username;
            $user->display_name = $displayName;
            $user->email = $email;
            $user->uses_gravatar = true;
            $user->save();

            return $user;
        }
    }

    public function avatar()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Image');
    }

    public function users()
    {
        return $this->hasMany('Poniverse\Ponyfm\ResourceUser', 'artist_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function comments()
    {
        return $this->hasMany('Poniverse\Ponyfm\Comment', 'profile_id')->orderBy('created_at', 'desc');
    }

    public function tracks()
    {
        return $this->hasMany('Poniverse\Ponyfm\Track', 'user_id');
    }

    public function getIsArchivedAttribute()
    {
        return (bool)$this->attributes['is_archived'];
    }

    public function getUrlAttribute()
    {
        return action('ArtistsController@getProfile', $this->slug);
    }

    public function getMessageUrlAttribute()
    {
        return 'http://mlpforums.com/index.php?app=members&module=messaging&section=send&do=form&fromMemberID=' . $this->id;
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
        $this->attributes['slug'] = Str::slug($value);
    }

    public function getAvatarUrl($type = Image::NORMAL)
    {
        if (!$this->uses_gravatar) {
            return $this->avatar->getUrl($type);
        }

        if ($this->email == "redacted@example.net") {
            return Gravatar::getUrl($this->id . "", Image::$ImageTypes[$type]['width'], "identicon");
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

    /**
     * Returns true if this user has the given role.
     *
     * @param $roleName
     * @return bool
     */
    public function hasRole($roleName)
    {
        foreach ($this->roles as $role) {
            if ($role->name === $roleName) {
                return true;
            }
        }

        return false;
    }
}
