<?php

namespace Courses;

use App\Utils\ValueFilter;
use App\Utils\ReadableDate;
use App\Models\AuthUser;
use DateTime;

class CourseItem
{
    use ValueFilter;

    // Good enough for now
    protected $data;

    public function __construct($fillable = [])
    {
        if (!is_array($fillable)) {
            throw new \InvalidArgumentException("Fillable must be an array");
        }
        $this->data = $fillable;
    }

    /**
     * @param \App\Http\Request $request
     */
    public static function loadFromForm($request)
    {
        $fillable = [];

        // Sync max length in UI
        $fillable['code'] = substr($request->post('code', ''), 0, 10);
        $fillable['title'] = substr($request->post('title', ''), 0, 100);
        $fillable['description'] = substr($request->post('description', ''), 0, 255);

        $fields = $request->postAll([
            'start_date',
            'end_date',
            'providers',
            'categories',
            'topics',
            'capabilities',
            'hide_in_further_leaning_search',
            'hide_in_employer_registration',
        ]);

        $fillable = array_merge($fillable, $fields);

        foreach ($fillable as $name => $val) {
            $fillable[$name] = filter_var($val, FILTER_SANITIZE_STRING);
        }

        foreach(['topics', 'providers', 'categories', 'capabilities'] as $tpc) {
            if (!empty($request->post($tpc))) {
                $selected = $request->post($tpc, '');
                $val = [];
                foreach (explode(",", $selected) as $id) {
                    $id = intval($id);
                    if ($id > 0) {
                        $val[] = $id;
                    }
                }
                $fillable[$tpc] = $val;
            } else {
                $fillable[$tpc] = [];
            }
        }

        return new self($fillable);
    }

    public static function loadFromAssignForm($request)
    {
        $fillable = [];

        $fillable['id'] = $request->post('id');
        $fillable['farms'] = [];
        $fillable['employers'] = '';

        $is_all = $request->post('all_farms', 'false') === 'true';
        if ($is_all) {
            $fillable['employers'] = 'all';
        } else {
            $selected = $request->post('assign_farms', '');
            $farms = [];
            foreach(explode(",", $selected) as $id) {
                $id = intval($id);
                if ($id > 0) {
                    $farms[] = $id;
                }
            }
            $fillable['farms'] = array_unique($farms);

            if (count($farms)) {
                $fillable['employers'] = implode(',', $farms);
            }
        }

        return new self($fillable);
    }

    public function get($key, $default = null)
    {
        return $this->valFromMap($this->data, $key, $default);
    }

    public function getId()
    {
        return intval($this->get('id', 0));
    }

    public function getCode()
    {
        return $this->get('code', '');
    }

    public function getTitle()
    {
        return $this->get('title', '');
    }

    public function getDescription()
    {
        return $this->get('description', '');
    }

    public function getStartDate()
    {
        return $this->get('start_date', '');
    }

    public function getReadableStartDate($format = 'date')
    {
        if ($this->getStartDate() !== '' && intval($this->getStartDate()) > 0) {
            return ReadableDate::transform($this->getStartDate())->$format;
        }
        return '';
    }

    public function getEndDate()
    {
        return $this->get('end_date', '');
    }

    public function getReadableEndDate($format = 'date')
    {
        if ($this->getEndDate() !== '' && intval($this->getEndDate()) > 0) {
            return ReadableDate::transform($this->getEndDate())->$format;
        }
        return '';
    }

    public function isArchived()
    {
        return boolval($this->get('archived', 0));
    }

    public function isHiddenInFurtherLearningSearch()
    {
        return boolval($this->get('hide_in_further_leaning_search', 0));
    }

    public function isHiddenInEmployerRegistration()
    {
        return boolval($this->get('hide_in_employer_registration', 0));
    }

    public function getTopics()
    {
        return $this->get('topics', []);
    }

    public function getCapabilities()
    {
        return $this->get('capabilities', []);
    }

    public function getProviders()
    {
        return $this->get('providers', []);
    }

    public function getParentCategories()
    {
        return $this->get('parent_categories', []);
    }

    public function getCategories()
    {
        return $this->get('categories', []);
    }

    public function getFarms()
    {
        return $this->get('farms', []);
    }

    public function isAllFarms()
    {
        return $this->get('employers', '') === 'all';
    }

    public function hasNoFarms()
    {
        return $this->get('employers', '') === '' && count($this->getFarms()) === 0;
    }

    public function hasFarms()
    {
        return !$this->isAllFarms() && !$this->hasNoFarms();
    }

    public function toArrayFull()
    {
        $item = $this->data;
        return $item;
    }

    public function validate($from_file = false)
    {
        $errors = [];

        // Redacted

        return $errors;
    }

    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public function toArray()
    {
        return $this->data;
    }

    public function toEncodedJson()
    {
        $json = [
            'topics' => $this->getTopics(),
            'capabilities' => $this->getCapabilities(),
            'pcategories' => $this->getParentCategories(),
            'categories' => $this->getCategories(),
            'providers' => $this->getProviders(),
            'farms' => $this->getFarms(),
        ];
        foreach($json as $key => $val) {
            $json[$key] = htmlentities(json_encode($val));
        }
        return $json;
    }
}