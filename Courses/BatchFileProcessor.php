<?php

namespace Courses;

use Exception;

class BatchFileProcessor
{

    public const HEADINGS = [
        'PROGRAMCODE',
        'PROGRAMNAME',
        'PROVIDERID',
        'PROVIDERNAME',
        'STARTDATE',
        'ENROLMENTENDDATE'
    ];

    /**
     * @var CourseRepository $repo
     */
    private $repo;

    public function __construct($repo = null)
    {
        $this->repo = $repo;
    }

    /**
     * @param $batch BatchFileReader
     * @return array
     * @throws Exception
     */
    public function processSingleFile($batch)
    {
        $save_bucket = [];
        $idc = $batch->getHeadingIndices(self::HEADINGS);

        $content = $batch->getSortedContentWithDateTime($idc, ['STARTDATE', 'ENROLMENTENDDATE']);

        $provider_ids = [];
        $provider_names = [];

        $errors = [];

        foreach ($content as $idx => $row) {
            $course = CoursePreviewItem::loadFromFile([
                'code' => $row['PROGRAMCODE'],
                'title' => $row['PROGRAMNAME'],
                'start_date' => $row['STARTDATE'],
                'end_date' => $row['ENROLMENTENDDATE'],
            ]);

            $provider_ids[$idx] = intval($row['PROVIDERID']);
            $provider_names[$idx] = filter_var(strtoupper(trim($row['PROVIDERNAME'])), FILTER_SANITIZE_STRING);

            $errors[$idx] = implode(", ", $course->validate(true));
            $save_bucket[] = $course;
        }

        $match_providers = $this->matchProviders($provider_ids, $provider_names);
        foreach ($errors as $idx => $e) {
            if (is_null($match_providers[$idx])) {
                $errors[$idx] = !empty($e) ? "No provider matched, " . $e : "No provider matched";
            } else {
                $save_bucket[$idx]->addProvider($match_providers[$idx]);
            }
        }

        return [
            'errors' => $errors,
            'has_errors' => !empty(implode('', $errors)),
            'courses' => $save_bucket,
            'providers' => $match_providers,
        ];
    }

    private function matchProviders($provider_ids, $provider_names)
    {
        // @codeCoverageIgnoreStart
        if (empty($provider_ids) && empty($provider_names)) {
            return [];
        }
        // @codeCoverageIgnoreEnd

        $match = $this->repo->findProvidersByIdOrName($provider_ids, $provider_names);
        $match_ids = [];
        $match_names = [];

        foreach ($match as $item) {
            $match_ids[intval($item['id'])] = $item;
            $match_names[strtoupper(trim($item['name']))] = $item;
        }

        $found = [];
        foreach (array_keys($provider_ids) as $idx) {
            $id = $provider_ids[$idx];
            $name = $provider_names[$idx];
            if (isset($match_ids[$id])) {
                $found[$idx] = $match_ids[$id];
            } elseif (isset($match_names[$name])) {
                $found[$idx] = $match_names[$name];
            } else {
                $found[$idx] = null;
            }
        }

        return $found;
    }
}
