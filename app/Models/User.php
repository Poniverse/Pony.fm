<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
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

namespace App\Models;

use App\Contracts\Commentable;
use App\Contracts\Searchable;
use App\Traits\IndexedInElasticsearchTrait;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Gravatar;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Str;
use League\OAuth2\Client\Token\AccessToken;
use Illuminate\Support\Facades\Validator;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * App\Models\User.
 *
 * @property int $id
 * @property string $display_name
 * @property string $username
 * @property bool $sync_names
 * @property string $email
 * @property string $gravatar
 * @property string $slug
 * @property bool $uses_gravatar
 * @property bool $can_see_explicit_content
 * @property string $bio
 * @property int $track_count
 * @property int $comment_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $avatar_id
 * @property string $remember_token
 * @property bool $is_archived
 * @property \Carbon\Carbon $disabled_at
 * @property-read \App\Models\Image $avatar
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ResourceUser[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Track[] $tracks
 * @property-read mixed $url
 * @property-read mixed $message_url
 * @property-read \Illuminate\Database\Eloquent\Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User userDetails()
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Notification[] $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $followers
 * @property-read mixed $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Activity[] $activities
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Activity[] $notificationActivities
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Email[] $emails
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EmailSubscription[] $emailSubscriptions
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereDisplayName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereSyncNames($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereGravatar($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereSlug($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereUsesGravatar($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereCanSeeExplicitContent($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereBio($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereTrackCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereCommentCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereAvatarId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereRememberToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereIsArchived($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereDisabledAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User withEmailSubscriptionFor($activityType)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User wherePoniverseId($poniverseId)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereLinkedToPoniverse()
 * @property int $redirect_to
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereRedirectTo($value)
 */
class User extends Model implements AuthenticatableContract, CanResetPasswordContract, \Illuminate\Contracts\Auth\Access\Authorizable, Searchable, Commentable
{
    use HasFactory;
    use Authenticatable, CanResetPassword, Authorizable, RevisionableTrait, IndexedInElasticsearchTrait;

    protected $elasticsearchType = 'user';
    protected $casts = [
        'id'                        => 'integer',
        'sync_names'                => 'boolean',
        'uses_gravatar'             => 'boolean',
        'can_see_explicit_content'  => 'boolean',
        'track_count'               => 'integer',
        'comment_count'             => 'integer',
        'avatar_id'                 => 'integer',
        'is_archived'               => 'boolean',
        'redirect_to'               => 'integer',
    ];
    protected $dates = ['created_at', 'updated_at', 'disabled_at'];
    protected $hidden = ['disabled_at', 'remember_token'];

    public function scopeUserDetails($query)
    {
        if (Auth::check()) {
            $query->with([
                'users' => function ($query) {
                    $query->whereUserId(Auth::user()->id);
                },
            ]);
        }

        return $query;
    }

    /**
     * Returns users with an email subscription to the given activity type.
     *
     * @param $query
     * @param int $activityType one of the TYPE_* constants in the Activity class
     * @return mixed
     */
    public function scopeWithEmailSubscriptionFor($query, int $activityType)
    {
        return $query->whereHas('emailSubscriptions', function ($query) use ($activityType) {
            $query->where('activity_type', $activityType);
        });
    }

    /**
     * Finds a user by their Poniverse account ID.
     *
     * @param $query
     * @param int $poniverseId
     * @return mixed
     */
    public function scopeWherePoniverseId($query, int $poniverseId)
    {
        return $query
            ->whereLinkedToPoniverse($query)
            ->where('oauth2_tokens.external_user_id', '=', $poniverseId);
    }

    /**
     * Filters the list of users to those who have a linked Poniverse account.
     *
     * @param $query
     * @return mixed
     */
    public function scopeWhereLinkedToPoniverse($query)
    {
        return $query
            ->join('oauth2_tokens', 'users.id', '=', 'oauth2_tokens.user_id')
            ->select('users.*', 'oauth2_tokens.external_user_id');
    }

    /**
     * Gets this user's OAuth access token record.
     *
     * @return AccessToken|null
     */
    public function getAccessToken()
    {
        $accessTokenRecord = DB::table('oauth2_tokens')->where('user_id', '=', $this->id)->first();

        if ($accessTokenRecord === null) {
            return null;
        } else {
            return new AccessToken([
                'access_token'      => $accessTokenRecord->access_token,
                'refresh_token'     => $accessTokenRecord->refresh_token,
                'expires'           => Carbon::parse($accessTokenRecord->expires)->timestamp,
                'resource_owner_id' => $accessTokenRecord->external_user_id,
            ]);
        }
    }

    /**
     * Updates this user's access token record.
     *
     * @param AccessToken $accessToken
     */
    public function setAccessToken(AccessToken $accessToken)
    {
        DB::table('oauth2_tokens')
            ->where('user_id', '=', $this->id)
            ->update([
                'access_token' => $accessToken->getToken(),
                'refresh_token' => $accessToken->getRefreshToken(),
                'expires' => Carbon::createFromTimestampUTC($accessToken->getExpires()),
                // NOTE: external_user_id does not get updated!
            ]);
    }

    /**
     * Takes the given string, slugifies it, and increments a counter if needed
     * to generate a unique slug version of it.
     *
     * @param string $name
     * @return string a unique slug
     */
    private static function getUniqueSlugForName(string $name):string
    {
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
            $user = new self;

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
        return $this->belongsToMany(self::class, 'followers', 'artist_id', 'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function comments():HasMany
    {
        return $this->hasMany(Comment::class, 'profile_id')->orderByDesc('created_at');
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

    public function emails()
    {
        return $this->hasManyThrough(Email::class, Notification::class, 'user_id', 'notification_id', 'id');
    }

    public function emailSubscriptions()
    {
        return $this->hasMany(EmailSubscription::class, 'user_id', 'id');
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
        if (! $this->uses_gravatar && $this->avatar !== null) {
            return $this->avatar->getUrl($type);
        }

        if ($this->email == 'redacted@example.net') {
            return Gravatar::getUrl($this->id.'', Image::$ImageTypes[$type]['width'], 'identicon');
        }

        $email = $this->gravatar;

        if (! strlen($email)) {
            $email = $this->email;
        }

        return Gravatar::getUrl($email, Image::$ImageTypes[$type]['width']);
    }

    public function getAvatarUrlLocal($type = Image::NORMAL)
    {
        if (! $this->uses_gravatar && $this->avatar !== null) {
            return $this->avatar->getFile($type);
        }

        if ($this->email == 'redacted@example.net') {
            return Gravatar::getUrl($this->id.'', Image::$ImageTypes[$type]['width'], 'identicon');
        }

        $email = $this->gravatar;

        if (! strlen($email)) {
            $email = $this->email;
        }

        return Gravatar::getUrl($email, Image::$ImageTypes[$type]['width']);
    }

    public function getSettingsUrl()
    {
        return route('account:settings', ['slug' => $this->slug]);
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
        return 'remember_token';
    }

    public function getUserAttribute():self
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceType():string
    {
        return 'profile';
    }

    public function activities():MorphMany
    {
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

    public static function mapPublicUserSummary(self $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->display_name,
            'slug' => $user->slug,
            'url' => $user->url,
            'is_archived' => $user->is_archived,
            'avatars' => [
                'small' => $user->getAvatarUrl(Image::SMALL),
                'normal' => $user->getAvatarUrl(Image::NORMAL),
            ],
            'created_at' => $user->created_at,
        ];
    }

    /**
     * Helper method that returns a row for every type of notifiable activity.
     * It's meant to be used for the notification settings screen.
     *
     * @return array
     */
    private function emailSubscriptionsJoined()
    {
        return DB::select('
            SELECT "subscriptions".*, "activity_types".* FROM
              (SELECT * FROM "email_subscriptions"
              WHERE "email_subscriptions"."deleted_at" IS NULL
              AND "email_subscriptions"."user_id" = ?) as "subscriptions"
            RIGHT JOIN "activity_types"
              ON "subscriptions"."activity_type" = "activity_types"."activity_type"
            ', [$this->id]);
    }

    /**
     * Generates an array of the user's notification settings. It's meant to be
     * used for the notification settings screen.
     *
     * @return array
     */
    public function getNotificationSettings()
    {
        $settings = [];
        $emailSubscriptions = $this->emailSubscriptionsJoined();

        foreach ($emailSubscriptions as $subscription) {
            // TODO: remove this check when news and album notifications are implemented
            if (! in_array($subscription->activity_type, [Activity::TYPE_NEWS, Activity::TYPE_PUBLISHED_ALBUM])) {
                $settings[] = [
                    'description' => $subscription->description,
                    'activity_type' => $subscription->activity_type,
                    'receive_emails' => $subscription->id !== null,
                ];
            }
        }

        return $settings;
    }

    /**
     * Returns this model in Elasticsearch-friendly form. The array returned by
     * this method should match the current mapping for this model's ES type.
     *
     * @return array
     */
    public function toElasticsearch():array
    {
        return [
            'username'      => $this->username,
            'display_name'  => $this->display_name,
            'tracks'        => $this->tracks->pluck('title'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBeIndexed():bool
    {
        return $this->disabled_at === null;
    }
}
