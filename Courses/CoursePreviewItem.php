<?php

namespace Courses;

use App\Utils\ValueFilter;
use App\Utils\ReadableDate;
use App\Models\AuthUser;
use DateTime;

class CoursePreviewItem extends CourseItem
{
    public static function loadFromFile($content)
    {
        $fillable = [];

        $fillable['code'] = substr($content['code'], 0, 10);
        $fillable['title'] = substr($content['title'], 0, 100);

        $fillable['start_date'] =
        ($content['start_date'] instanceof DateTime) ?
            $content['start_date']->format("Y-m-d") : '';
        $fillable['end_date'] =
        ($content['end_date'] instanceof DateTime) ?
            $content['end_date']->format("Y-m-d") : '';

        foreach ($fillable as $name => $val) {
            $fillable[$name] = filter_var($val, FILTER_SANITIZE_STRING);
        }

        $item = new static($fillable);
        return $item;
    }

    public function addProvider($provider)
    {
        $this->data['providers'] = [$provider['id']];
    }

    public function addTopic($topic)
    {
        $this->data['topics'] = [$topic['id']];
    }

    public function addCapability($capability)
    {
        $this->data['capabilities'] = [$capability['id']];
    }


}