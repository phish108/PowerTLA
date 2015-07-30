<?php

include_once 'Services/Membership/classes/class.ilParticipants.php';

require_once 'Modules/Course/classes/class.ilObjCourse.php';

class CourseBroker extends Logger
{
    private $iliasVersion;

    public function __construct($iV)
    {
        $this->iliasVersion = $iV;
    }

    public function getCourseList()
    {
        global $ilUser, $ilObjDataCache;

        $retval = array();

        // got all courses for the user

        $items = ilParticipants::_getMembershipByType($ilUser->getId(), 'crs');

        foreach($items as $obj_id)
        {
            // 1 get course meta data
            $crs = new ilObjCourse($obj_id, false);
            $crs->read();

            $title       = $crs->getTitle();
			$description = $crs->getDescription();

            $course = array("id" => $obj_id,
                            "title" => $title,
                            "description" => $description);

            // 2 get course objects
            $item_references = ilObject::_getAllReferences($obj_id);
            reset($item_references);

            if (strcmp($this->iliasVersion, "4.2") === 0)
            {
                foreach($item_references as $ref_id => $x ) {
                    // Antique Ilias

                    // For some strange reason remains the $crs->getRefId() remains empty
                    $crs = new ilObjCourse($ref_id);

                    require_once 'Modules/Course/classes/class.ilCourseItems.php';
                    $courseItems = new ilCourseItems($crs->getRefId(), 0, $ilUser->getId());
                    $courseItemList = $courseItems->getAllItems();
                    $course["content-type"] = $this->mapItems($courseItemList);
                    break;
                 }
            }
            else
            {
                // Modern Ilias
                foreach($item_references as $ref_id => $x ) {
                    $crs = new ilObjCourse($ref_id);
                    $courseItemList = $crs->getSubItems();

                    // TODO check with Ilias 4.4 and 4.3
//                    $this->log(">>> IL >>> " . json_encode($courseItemList["_all"]));
                    $course["content-type"] = $this->mapItemsType($courseItemList);
                }
            }
            array_push($retval, $course);
        }
        return $retval;
    }

    protected function mapItems($itemList)
    {
        $ctList = array();
        if ($itemList && count($itemList)) {
            foreach($itemList as $courseItem) {
                // $this->log("Course Item: " . json_encode($courseItem));
                // map the ILIAS types to fake MIME types
                switch ($courseItem["type"])
                {
                    case "crs":
                    case "lm":
                        $type = "x-application/imscp"; // IMS Content Package
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "sco":
                        $type = "x-application/imscp+imsss"; // IMS Content Package
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "pg":
                    case "page":
                    case "chap":
                    case "htlm":
                        $type = "text/html";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "tst": // tst should be the same as qpl
                        $type = "x-application/imsqti-test";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "qpl":
                        $type = "x-application/imsqti";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "spl": // generic survey pool
                    case "svy": // a survey form
                        $type = "x-application/x-form";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "glo":
                        $type = "x-application/x-glossary";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "webr":
                        $type = "text/url";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "file":
                    case "ass":
                        // Images or Files
                        $type = "x-application/assest";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $ctList;
    }

    protected function mapItemsType($itemList)
    {
        $ctList = array();
        if ($itemList && count($itemList)) {
            foreach($itemList as $courseItem => $x) {
                // $this->log("Course Item: " . json_encode($courseItem));
                // map the ILIAS types to fake MIME types
                switch ($courseItem)
                {
                    case "crs":
                    case "lm":
                        $type = "x-application/imscp"; // IMS Content Package
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "sco":
                        $type = "x-application/imscp+imsss"; // IMS Content Package
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "pg":
                    case "page":
                    case "chap":
                    case "htlm":
                        $type = "text/html";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "tst": // tst should be the same as qpl
                        $type = "x-application/imsqti-test";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "qpl":
                        $type = "x-application/imsqti";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "spl": // generic survey pool
                    case "svy": // a survey form
                        $type = "x-application/x-form";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "glo":
                        $type = "x-application/x-glossary";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "webr":
                        $type = "text/url";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    case "file":
                    case "ass":
                        // Images or Files
                        $type = "x-application/assest";
                        if (array_search($type, $ctList) === FALSE)
                        {
                            array_push($ctList, $type);
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $ctList;
    }
}
?>