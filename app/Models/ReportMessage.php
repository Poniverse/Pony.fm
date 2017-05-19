<?php
/**
 * Created by IntelliJ IDEA.
 * User: Joe
 * Date: 19/05/2017
 * Time: 08:09
 */

namespace Poniverse\Ponyfm\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Poniverse\Ponyfm\Models\ReportMessage
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $report_id
 * @property string $message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */

class ReportMessage extends Model
{
    protected $dates = ['created_at', 'updated_at'];
}
