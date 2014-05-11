<?php

namespace DvLab\DeploymentBuildsExecutor;

class Result
{

    /**
     * @var int
     */
    private $returnCode;

    /**
     * @var array
     */
    private $output;

    public function __construct($returnCode, $output)
    {
        $this->returnCode = $returnCode;
        $this->output     = $output;
    }

    /**
     * @return int
     */
    public function getReturnCode()
    {
        return $this->returnCode;
    }

    /**
     * @return array
     */
    public function getOutput()
    {
        return $this->output;
    }

} 
