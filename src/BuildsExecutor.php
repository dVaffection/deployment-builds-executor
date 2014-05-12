<?php

namespace DvLab\DeploymentBuildsExecutor;

class BuildsExecutor
{

    /**
     * @var string
     */
    private $buildsDir;

    /**
     * @var string
     */
    private $lastBuildFilename;

    /**
     * @param string $buildsDir
     * @param string $latestBuildFilename
     *
     * @throws \UnexpectedValueException
     */
    public function __construct($buildsDir, $latestBuildFilename)
    {
        if (!is_readable($buildsDir)) {
            $message = sprintf('Directory "%s" is not readable', $buildsDir);
            throw new \UnexpectedValueException($message);
        }

        if (!is_readable($latestBuildFilename)) {
            $message = sprintf('`$latestBuildFilename` "%s" is not readable', $latestBuildFilename);
            throw new \UnexpectedValueException($message);
        }

        if (!is_writable($latestBuildFilename)) {
            $message = sprintf('`$latestBuildFilename` "%s" is not writable', $latestBuildFilename);
            throw new \UnexpectedValueException($message);
        }

        $this->buildsDir         = $buildsDir;
        $this->lastBuildFilename = $latestBuildFilename;
    }

    /**
     * Executes new builds. Stops on error (if execution of a build returns non-zero status code)
     * Keeps track of the latest executed build.
     *
     * @return Result
     * @throws \UnexpectedValueException
     */
    public function executeNewBuilds()
    {
        $returnCode = 0;
        $output     = array();

        $files = $this->getNewBuilds();
        foreach ($files as $file) {
            if (!is_executable($file)) {
                $message = sprintf('File "%s" must be executable', $file);
                throw new \UnexpectedValueException($message);
            }

            $fileReturnCode = 0;
            $fileOutput     = array();
            exec($file, $fileOutput, $fileReturnCode);

            $output[] = $fileOutput;
            if ($fileReturnCode > 0) {
                $returnCode = $fileReturnCode;
                break;
            }

            $latestBuildName = pathinfo($file, PATHINFO_FILENAME);
            $this->writeLatestBuildName($latestBuildName);
        }

        return new Result($returnCode, $output);
    }

    /**
     * Returns an array of new build file names
     *
     * @throws \UnexpectedValueException
     * @return array
     */
    public function getNewBuilds()
    {
        $lastBuildFilename = $this->getLatestBuildName();
        $iterator          = $this->getBuilds();

        $files = array();
        /** @var $fileInfo \splFileInfo */
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->getFilename() > $lastBuildFilename) {
                array_push($files, $fileInfo->getPathname());
            }
        }

        // sort builds in timely order
        sort($files);

        return $files;
    }

    /**
     * @return BuildFilesFilter
     */
    protected function getBuilds()
    {
        $iterator = new \DirectoryIterator($this->buildsDir);

        return new BuildFilesFilter($iterator);
    }

    /**
     * @throws \UnexpectedValueException
     * @return string
     */
    protected function getLatestBuildName()
    {
        $data = @file_get_contents($this->lastBuildFilename);
        if (!empty($data) && !is_numeric($data)) {
            $message = sprintf('Latest build name must be an empty or a numeric string, given "%s"', $data);
            throw new \UnexpectedValueException($message);
        }

        return $data;
    }

    /**
     * @param string $latestBuildName
     *
     * @throws \RuntimeException
     */
    protected function writeLatestBuildName($latestBuildName)
    {
        $result = file_put_contents($this->lastBuildFilename, $latestBuildName);
        if (false === $result) {
            $message = 'Can not write latest build filename: ' . $this->lastBuildFilename;
            throw new \RuntimeException($message);
        }
    }

}
