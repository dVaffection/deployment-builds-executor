<?php

namespace DvLab\DeploymentBuildsExecutor;

use DvLab\DeploymentBuildsExecutor\BuildFilesFilter;

/**
 * @group unit
 */
class BuildFilesFilterTest extends \PHPUnit_Framework_TestCase
{

    private static $pathname;

    public static function setUpBeforeClass()
    {
        self::$pathname = sprintf('%s/%s/%s/build-filter-test', sys_get_temp_dir(), md5(__CLASS__), rand());

        self::createDir();
        self::createBuildFiles();
    }

    public function test()
    {
        $directoryIterator = new \DirectoryIterator(self::$pathname);
        $iterator          = new BuildFilesFilter($directoryIterator);

        $baseNames = array(
            '20140510220200',
            '20140510220100',
        );
        foreach ($iterator as $filInfo) {
            $fileName = $filInfo->getFilename();
            $index    = array_search($fileName, $baseNames, true);
            if (false === $index) {
                $this->fail('Unexpected filename: ' . $fileName);
            } else {
                unset($baseNames[$index]);
            }
        }
        $this->assertEmpty($baseNames);
    }

    private static function createDir()
    {
        $result = @mkdir(self::$pathname, 0777, true);
        if (!$result) {
            $message = 'Could not create directory: ' . self::$buildsDir;
            throw new \RuntimeException($message);
        }
    }

    private static function createBuildFiles()
    {
        $baseNames = array(
            '20140510220200',
            '20140510220100',
            'non-digits',
        );

        foreach ($baseNames as $basename) {
            $filename = self::$pathname . DIRECTORY_SEPARATOR . $basename;
            $result   = @file_put_contents($filename, '');
            if (false === $result) {
                $message = 'Could not create filename: ' . $filename;
                throw new \RuntimeException($message);
            }
        }
    }

} 
