<?php

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Response;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Models\Report;

class ReportController extends ApiControllerBase
{
    public function getReportCategories($type) {
        $type_id = Report::getResourceTypeIdFromString($type);
        $categories = Report::getCategories($type_id);
        return Response::json(['categories' => $categories]);
    }

    public function getReports() {
        $reports = Report::all();
        $reports[0]['type_string'] = $reports[0]->resource();
        return $reports;
    }
}
