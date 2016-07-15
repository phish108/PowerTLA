<?php
namespace PowerTLA\Moodle\Handler\Content;

use PowerTLA\Handler\BaseHandler;

include_once($CFG->libdir . '/moodlelib.php');
include_once($CFG->dirroot . '/course/lib.php');
include_once($CFG->libdir . '/coursecatlib.php');

class CourseBroker extends BaseHandler
{

    public function getUserCourse($courseId)
    {
        global $DB;

        // return course details

        return null;
    }

    public function getCourseList()
    {
        $fieldMap = [
            "id" => "course_id",
            "short_name" => "shortname",
            "display_name" => "fullname"
        ];

        $retval = [];

        $sortorder = 'visible DESC, sortorder ASC';

        if ($courses = enrol_get_my_courses(NULL, $sortorder)) {
            foreach ($courses as $course) {
                if (!$course->visible) {
                    next;
                }

                $c = [];

                foreach ($fieldMap as $k => $v) {
                    if (property_exists($course, $k)) {
                        $c[$v] = $course->$k;
                    }
                }

                // ignore course files/images
                // FIXME: add references to icons and images.

                $retval[] = $c;
            }
        }

        return $retval;
    }

    public function getPublicCourses()
    {
        // should return all available public courses in the system
        //  public is either with guest access or self enrollment
        return [];
    }
}

?>
