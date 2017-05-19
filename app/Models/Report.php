<?php

namespace Poniverse\Ponyfm\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * Poniverse\Ponyfm\Models\Report
 *
 * @property integer $id
 * @property integer $reporter_id
 * @property integer $resource_type
 * @property integer $resource_id
 * @property integer $category
 * @property string $message
 * @property bool $resolved
 * @property integer $resolved_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $resolved_at
 */

class Report extends Model
{
    protected $dates = ['created_at', 'updated_at', 'resolved_at'];

    /**
     * These constants are an implementation detail of this model and should
     * not be used directly in other classes. They're used to efficiently
     * store the type of resource this notification is about in the database.
     *
     * The "resource_type" attribute is transformed into a class name at runtime
     * so that the use of an integer in the database to represent this info
     * remains an implementation detail of this model. Outside of this class,
     * the resource_type attribute should be treated as a fully-qualified class
     * name.
     */
    const TYPE_TRACK = 1;
    const TYPE_USER = 2;
    const TYPE_COMMENT = 3;

    const TYPE_NAMES = [
        Report::TYPE_TRACK => "track",
        Report::TYPE_USER => "user",
        Report::TYPE_COMMENT => "comment"
    ];

    /**
     * These values are stored in the "report_categories" table
     * Make sure when adding a new category that you write a migration
     * to update that table as well.
     */
    const CATEGORY_COPYRIGHT = 1;
    const CATEGORY_HARASSMENT = 2;
    const CATEGORY_OFFENSIVE = 3;
    const CATEGORY_SPAM = 4;
    const CATEGORY_NONPONY = 5;

    /**
     * You shouldn't be able to report comments for copyright infringement
     * or being 'non-pony'. Here we outline which categories we can use
     */
    const COMMENT_REPORT_CATEGORIES = [
        Report::CATEGORY_HARASSMENT,
        Report::CATEGORY_OFFENSIVE,
        Report::CATEGORY_SPAM
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id', 'id');
    }

    public function resource()
    {
        return $this->morphTo('resource', 'resource_type', 'resource_id');
    }

    public function getResourceTypeAttribute($value)
    {
        switch ($value) {
            case static::TYPE_TRACK:
                return Track::class;

            case static::TYPE_USER:
                return User::class;

            case static::TYPE_COMMENT:
                return Comment::class;

            default:
                // Null must be returned here for Eloquent's eager-loading
                // of the polymorphic relation to work.
                return null;
        }
    }

    public function setResourceTypeAttribute($value)
    {
        switch ($value) {
            case Track::class:
                $this->attributes['resource_type'] = static::TYPE_TRACK;
                break;

            case User::class:
                $this->attributes['resource_type'] = static::TYPE_USER;
                break;

            case Comment::class:
                $this->attributes['resource_type'] = static::TYPE_COMMENT;
                break;
        }
    }

    public function getResourceTypeString():string
    {
        return $this->getResourceTypeStringFromId($this->resource_type);
    }

    public static function getResourceTypeStringFromId($id):string {
        if (array_key_exists($id, Report::TYPE_NAMES)) {
            return Report::TYPE_NAMES[$id];
        }

        throw new \Exception("Unknown resource type id {$id}");
    }

    public static function getResourceTypeIdFromString($name):int {
        $key = array_search($name, Report::TYPE_NAMES);
        if ($key) {
            return $key;
        }

        throw new \Exception("Unknown resource type {$name}");
    }

    public static function getCategories($type = null) {
        $catQuery = DB::table('report_categories')->select('*');

        if ($type == Report::TYPE_COMMENT) {
            $catQuery->whereIn('report_category', Report::COMMENT_REPORT_CATEGORIES);
        }

        $categories = $catQuery->get();
        return $categories;
    }
}
