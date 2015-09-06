<?php
class CourseService extends VLEService
{
    public static function apiDefinition($prefix, $name="", $link="")
    {
        return parent::apiDefinition($prefix, "powertla.content.courselist", "course.php");
    }

    protected function initializeRun()
    {
        $this->VLE->getSessionValidator()->rejectTokenType("Client");
    }

    /**
     * @protected @function get()
     *
     * returns the courselist of the present user.
     *
     * if no user is authenticated (!VLESession->active()) then the function
     * will return a dummy course for the public data. Each public data will get
     * wrapped into a dummy course with a single item.
     *
     * if the guest user is enrolled into a course then the service will return these
     * courses, too.
     */
    protected function get()
    {
        $this->data = array();

        $cbH = $this->VLE->getCourseBroker();
        if ($cbH)
        {
            $this->data = $cbH->getCourseList();
        }
    }
}
?>
