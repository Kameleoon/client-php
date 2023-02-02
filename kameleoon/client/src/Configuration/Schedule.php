<?php

namespace Kameleoon\Configuration;

class Schedule
{
    public $dateStart;
    public $dateEnd;

    public function __construct($schedule) {
        $this->dateStart = $this->fetchDate($schedule, "dateStart");
        $this->dateEnd = $this->fetchDate($schedule, "dateEnd");
    }

    private function fetchDate($schedule, $key) {
        return isset($schedule->$key) ? strtotime($schedule->$key) : null;
    }
}

?>
