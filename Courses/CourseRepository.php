<?php

namespace Courses;

use App\Enums\UserLevel;
use App\Models\AuthUser;
use App\Utils\ValueFilter;

class CourseRepository
{
    use ValueFilter;

    private $db;

    private $i2db;

    private $search;

    public function __construct($db, $i2db)
    {
        $this->db = $db;
        $this->i2db = $i2db;
        $this->search = new CourseSearchRepository($db, $i2db);
    }

    public function findProvidersByIdOrName($ids = [], $names = [])
    {
        foreach ($names as $k => $n) {
            $names[$k] = sprintf("'%s'", trim($n));
        }

        $uniq_ids = implode(",", array_unique($ids));
        $uniq_names = implode(",", array_unique($names));
        $filters = [];

        $sql = "SELECT id, name FROM providers WHERE ";
        if (!empty($uniq_ids)) {
            $filters[] = sprintf("id IN (%s)", $uniq_ids);
        }

        if (!empty($uniq_names)) {
            $filters[] = sprintf("name IN (%s)", $uniq_names);
        }

        $sql = $sql . implode(" OR ", $filters);
        return $this->db->get_results($sql, ARRAY_A);
    }
}
