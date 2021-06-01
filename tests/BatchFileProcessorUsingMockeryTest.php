<?php

use Courses\BatchFileProcessor;
use Courses\BatchFileReader;
use Courses\CourseItem;
use Courses\CourseRepository;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class BatchFileProcessorUsingMockeryTest extends MockeryTestCase
{
    public function dataProvider(): array
    {
        $batch_stub = \Mockery::mock(BatchFileReader::class);
        $batch_stub->shouldReceive(['getHeadingIndices' => array_flip(BatchFileProcessor::HEADINGS)]);
        $repo_mock = \Mockery::mock(CourseRepository::class);
        $repo_mock->shouldReceive([
            'findProvidersByIdOrName' => [
                ['id' => 1, 'name' => 'University of Finance'],
                ['id' => 2, 'name' => 'University of Social Sciences'],
                ['id' => 3, 'name' => 'University of Economics'],
            ]
        ]);

        return [
            [$batch_stub, $repo_mock]
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testProviderMatched(BatchFileReader $batch_stub, CourseRepository $repo_mock): void
    {
        $test_data = [
            'PROGRAMCODE' => 'ECON101',
            'PROGRAMNAME' => 'Economics 101',
            'PROVIDERID' => 3,
            'PROVIDERNAME' => '',
            'STARTDATE' => DateTime::createFromFormat('Y-m-d', '2019-11-01'),
            'ENROLMENTENDDATE' => DateTime::createFromFormat('Y-m-d', '2019-10-01')
        ];

        $batch_stub->shouldReceive('getSortedContentWithDateTime')
            ->andReturn([$test_data]);

        $processor = new BatchFileProcessor($repo_mock);
        $result = $processor->processSingleFile($batch_stub);
        $this->assertFalse($result['has_errors']);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testProviderNoMatch(BatchFileReader $batch_stub, CourseRepository $repo_mock): void
    {
        $test_data = [
            'PROGRAMCODE' => 'FINANCE201',
            'PROGRAMNAME' => 'Finance Management 201',
            'PROVIDERID' => 4,
            'PROVIDERNAME' => 'Finance School of Wizardry',
            'STARTDATE' => DateTime::createFromFormat('Y-m-d', '2019-11-01'),
            'ENROLMENTENDDATE' => DateTime::createFromFormat('Y-m-d', '2019-10-01')
        ];

        $batch_stub->shouldReceive('getSortedContentWithDateTime')
            ->andReturn([$test_data]);

        $processor = new BatchFileProcessor($repo_mock);
        $result = $processor->processSingleFile($batch_stub);
        $this->assertTrue($result['has_errors']);
        $this->assertContains('No provider matched', $result['errors']);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testProviderMatchWithId(BatchFileReader $batch_stub, CourseRepository $repo_mock): void
    {
        $test_data = [
            'PROGRAMCODE' => 'FINANCE201',
            'PROGRAMNAME' => 'Finance Management 201',
            'PROVIDERID' => 2,
            'PROVIDERNAME' => '',
            'STARTDATE' => DateTime::createFromFormat('Y-m-d', '2019-11-01'),
            'ENROLMENTENDDATE' => DateTime::createFromFormat('Y-m-d', '2019-10-01')
        ];

        $batch_stub->shouldReceive('getSortedContentWithDateTime')
            ->andReturn([$test_data]);

        $processor = new BatchFileProcessor($repo_mock);
        $result = $processor->processSingleFile($batch_stub);
        $this->assertFalse($result['has_errors']);
        $this->assertContains(2, array_pop($result['providers']));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testProviderMatchWithName(BatchFileReader $batch_stub, CourseRepository $repo_mock): void
    {
        $test_data = [
            'PROGRAMCODE' => 'FINANCE201',
            'PROGRAMNAME' => 'Finance Management 201',
            'PROVIDERID' => '',
            'PROVIDERNAME' => 'University of Finance',
            'STARTDATE' => DateTime::createFromFormat('Y-m-d', '2019-11-01'),
            'ENROLMENTENDDATE' => DateTime::createFromFormat('Y-m-d', '2019-10-01')
        ];

        $batch_stub->shouldReceive('getSortedContentWithDateTime')
            ->andReturn([$test_data]);

        $processor = new BatchFileProcessor($repo_mock);
        $result = $processor->processSingleFile($batch_stub);
        $this->assertFalse($result['has_errors']);
        $this->assertContains('University of Finance', array_pop($result['providers']));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testProviderCourseMatched(BatchFileReader $batch_stub, CourseRepository $repo_mock): void
    {
        $test_data = [
            'PROGRAMCODE' => 'ECON101',
            'PROGRAMNAME' => 'Economics 101',
            'PROVIDERID' => 3,
            'PROVIDERNAME' => '',
            'STARTDATE' => DateTime::createFromFormat('Y-m-d', '2019-11-01'),
            'ENROLMENTENDDATE' => DateTime::createFromFormat('Y-m-d', '2019-10-01')
        ];

        $batch_stub->shouldReceive('getSortedContentWithDateTime')
            ->andReturn([$test_data]);

        $processor = new BatchFileProcessor($repo_mock);
        $result = $processor->processSingleFile($batch_stub);
        $this->assertTrue(array_pop($result['courses']) instanceof CourseItem);
    }
}
