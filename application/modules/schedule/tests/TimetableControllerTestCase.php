<?php

class TimetableControllerTestCase extends ControllerTestCase
{
    public function testCallingRootTriggerTimetable()
    {
        $this->dispatch("/schedule/timetable/");
        $this->assertModule('schedule');
        $this->assertController('timetable');
        $this->assertAction('index');

    }
}

?>