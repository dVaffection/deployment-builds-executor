<?php

namespace DvLab\DeploymentBuildsExecutor;

use DvLab\DeploymentBuildsExecutor\BuildsExecutor;

/**
 * @group unit
 */
class BuildsExecutorTest extends \PHPUnit_Framework_TestCase
{

    private static $buildsDir;

    public static function setUpBeforeClass()
    {
        self::$buildsDir = sprintf('%s/%s/%s/build-executor-test', sys_get_temp_dir(), md5(__CLASS__), rand());

        self::createDir();
        self::createBuildFiles();
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function buildsDirectoryIsNotReadable()
    {
        new BuildsExecutor('not readable', 'does not matter');
    }

    /**
     * @test
     */
    public function getNewBuilds()
    {
        // all build files are in place and sorted in timely order
        $latestBuildFilename = self::$buildsDir . DIRECTORY_SEPARATOR . 'latest-build';
        $buildsExecutor      = new BuildsExecutor(self::$buildsDir, $latestBuildFilename);
        $actual              = $buildsExecutor->getNewBuilds();
        $expected            = array(
            self::$buildsDir . DIRECTORY_SEPARATOR . '1394656042',
            self::$buildsDir . DIRECTORY_SEPARATOR . '1394656043',
            self::$buildsDir . DIRECTORY_SEPARATOR . '1394656044',
            self::$buildsDir . DIRECTORY_SEPARATOR . '1394656045',
        );
        $this->assertSame($expected, $actual);


        // only build files later than 1394656043 are returned
        $latestBuildFilename = self::createLatestBuildFilename('1394656043');
        $buildsExecutor      = new BuildsExecutor(self::$buildsDir, $latestBuildFilename);
        $actual              = $buildsExecutor->getNewBuilds();
        $expected            = array(
            self::$buildsDir . DIRECTORY_SEPARATOR . '1394656044',
            self::$buildsDir . DIRECTORY_SEPARATOR . '1394656045',
        );
        $this->assertSame($expected, $actual);


        // no build files
        $latestBuildFilename = self::createLatestBuildFilename('1394656045');
        $buildsExecutor      = new BuildsExecutor(self::$buildsDir, $latestBuildFilename);
        $actual              = $buildsExecutor->getNewBuilds();
        $this->assertEmpty($actual);
    }

    /**
     * @test
     */
    public function executeNewBuilds()
    {
        // build file is not executable
        $filename = self::$buildsDir . DIRECTORY_SEPARATOR . '1394656042';
        $result   = chmod($filename, 0444);
        if (false === $result) {
            $message = sprintf('Can not set file "%s" permissions', $filename);
            throw new \RuntimeException($message);
        }
        $latestBuildFilename = self::createLatestBuildFilename('');
        $buildsExecutor      = new BuildsExecutor(self::$buildsDir, $latestBuildFilename);
        try {
            $buildsExecutor->executeNewBuilds();
            $message = sprintf('Must not proceed because file %s is non-executable', $filename);
            $this->fail($message);
        } catch (\UnexpectedValueException $e) {
        }


        // execution is failed on the 1394656043
        $latestBuildFilename = self::createLatestBuildFilename('1394656042');
        $buildsExecutor      = new BuildsExecutor(self::$buildsDir, $latestBuildFilename);
        $result              = $buildsExecutor->executeNewBuilds();

        $this->assertInstanceOf('DvLab\DeploymentBuildsExecutor\Result', $result);
        $this->assertGreaterThan(0, $result->getReturnCode());


        // builds are successfully executed
        $latestBuildFilename = self::createLatestBuildFilename('1394656043');
        $buildsExecutor      = new BuildsExecutor(self::$buildsDir, $latestBuildFilename);
        $result              = $buildsExecutor->executeNewBuilds();

        $this->assertSame(0, $result->getReturnCode());
        foreach ($result->getOutput() as $output) {
            $expected = array('success');
            $this->assertSame($expected, $output);
        }


        return $latestBuildFilename;
    }

    /**
     * @bugifx https://github.com/dVaffection/deployment-builds-executor/issues/4
     * @test
     * @depends executeNewBuilds
     */
    public function latestBuildNameMustBeANumericString($latestBuildFilename)
    {
        $contents = file_get_contents($latestBuildFilename);
        if (false === $contents) {
            $message = 'Can not read file: ' . $latestBuildFilename;
            throw new \RuntimeException($message);
        }

        $this->assertTrue(is_numeric($contents));
    }

    private static function createDir()
    {
        $result = @mkdir(self::$buildsDir, 0777, true);
        if (!$result) {
            $message = 'Could not create directory: ' . self::$buildsDir;
            throw new \RuntimeException($message);
        }
    }

    private static function createBuildFiles()
    {
        $baseNames = array(
            '1394656044' => 'echo "success"',
            '1394656043' => "#!/usr/bin/env php\n<?php\n new undefined();",
            '1394656042' => 'echo "success"',
            '1394656045' => 'echo "success"',
        );

        foreach ($baseNames as $baseName => $content) {
            $filename = self::$buildsDir . DIRECTORY_SEPARATOR . $baseName;
            $bytes    = @file_put_contents($filename, $content);
            if (false === $bytes) {
                $message = 'Could not create filename: ' . $filename;
                throw new \RuntimeException($message);
            }
            $result = @chmod($filename, 0755);
            if (false === $result) {
                $message = sprintf('Can not set file "%s" permissions', $filename);
                throw new \RuntimeException($message);
            }
        }
    }

    private static function createLatestBuildFilename($buildName)
    {
        $filename = self::$buildsDir . DIRECTORY_SEPARATOR . 'latest-build';

        if (file_exists($filename)) {
            $result = @chmod($filename, 0755);
            if (false === $result) {
                $message = sprintf('Can not set file "%s" permissions', $filename);
                throw new \RuntimeException($message);
            }
        }

        $bytes = file_put_contents($filename, $buildName);
        if (false === $bytes) {
            $message = 'Could not create filename: ' . $filename;
            throw new \RuntimeException($message);

        }


        return $filename;
    }

} 
