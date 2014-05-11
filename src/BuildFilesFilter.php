<?php

namespace DvLab\DeploymentBuildsExecutor;

class BuildFilesFilter extends \FilterIterator
{

    public function __construct(\DirectoryIterator $iterator)
    {
        parent::__construct($iterator);
    }

    /**
     * Interested in numeric file names only
     *
     * @return boolean
     */
    public function accept()
    {
        /* @var $fileInfo \splFileInfo */
        $fileInfo = $this->current();

        return $fileInfo->isFile() && is_numeric($fileInfo->getBasename());
    }

} 
