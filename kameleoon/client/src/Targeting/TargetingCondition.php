<?php
namespace Kameleoon\Targeting;

abstract class TargetingCondition
{
    private $type;

    private $include;

    public abstract function check($data);

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getInclude()
    {
        return $this->include;
    }

    public function setInclude($include)
    {
        $this->include = $include;
    }
}
