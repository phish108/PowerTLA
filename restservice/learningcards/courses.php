<?php
/* 	THIS COMMENT MUST NOT BE REMOVED


Copyright (c) 2012 ETH Zürich, Affero GPL, see
         backend/ILIAS/AGPL_LICENSE.txt
   	if you don't have a license file, then you can obtain it from the projet's
    page on github
     <https://github.com/ISN-Zurich/ISN-Learning-Cards/blob/master/backEnd/ILIAS/LICENSE.txt>

	This file is part of Mobler Cards ILIAS Backend.

    Mobler Cards Ilias Backend is free software: you can redistribute this
    code and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Mobler Cards Ilias Backend  is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with Ilias Backend. If not, see <http://www.gnu.org/licenses/>.
*/


/**
 * This class loads the courses for the in the header specified user id from ILIAS and
 * returns a json-object with the course list
 *
 * @author Isabella Nake
 * @author Evangelia Mitsopoulou
 *
 */


//syncTimeOut provides a way for the server to tell the clients how often they are allowed to synchronize
$SYNC_TIMEOUT = 60000;

require_once './common.php';

$ilpath = findIliasInstance();
if (!empty($ilpath))
{
    global $ilUser;

    global $DEBUG, $class_for_logging;

    $DEBUG = 0;
    $class_for_logging = "courses.php";

    $userID = get_session_user_from_headers();
    logging(" my userid is ". $userID);

    $return_data = getCourseList($userID);

    header('content-type: application/json');
    echo (json_encode($return_data));
}

/**
 * Gets the course list for the specified user
 *
 * @return course list array
 */
function getCourseList($userId) {
	global $ilObjDataCache;

	include_once 'Services/Membership/classes/class.ilParticipants.php';
	//require_once 'Modules/Course/classes/class.ilCourseItems.php';

    require_once 'Modules/Course/classes/class.ilObjCourse.php';
	require_once 'Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php';

	//loads all courses in which the current user is a member
	$items = ilParticipants::_getMembershipByType($userId, 'crs');
	logging("items are ".json_encode($items));
	$courses = array();
	foreach($items as $obj_id)	{

        // NOTE
        // we must never access a course through its object ID but through its reference ID.
        // This has changed since the version 4.2 series

        $item_references = ilObject::_getAllReferences($obj_id);

        // NOTE: reset() returns the first value of the reference list
        $crs = new ilObjCourse(reset($item_references), true);
        logging('2');
        // should return all item in the course

        $courseItemList = $crs->getSubItems();

        logging('3');
        logging("subitems are ".json_encode($courseItemList));

        // we should be able to fetch the items as following
        // $qpl = $crs->items["qpl"]; // which returns a key -> obj array

		//check if valid questionpool for the course exists
		$validQuestionPool = false;

        // NOTE, subitems returns an associative array since 4.3
        foreach($courseItemList["_all"] as $courseItem) {
            //the course item has to be of type "qpl" (= questionpool)
            if (strcmp($courseItem["type"], "qpl") == 0) {
                logging("course " . $obj_id . " has question pool");

                //get the question pool
                $questionPool = new ilObjQuestionPool($courseItem["ref_id"]);
                $questionPool->read();

                //calls isValidQuestionPool in questions.php
                if (isValidQuestionPool($questionPool)) {
                    logging("valid question pool");
                    $validQuestionPool = true;
                }
			}
		}

		//if the question pool is valid, the course is added to the list
		if ($validQuestionPool) {
			$title       = $ilObjDataCache->lookupTitle($obj_id);
			$description = $ilObjDataCache->lookupDescription($obj_id);

			array_push($courses,
					array("id"             => $obj_id,
                          "title"        => $title,
                          "syncDateTime" => 0,
                          "syncState"    => false,
                          "isLoaded"     => false,
                          "description"  => $description));
		}

	}

	//data structure for frontend models
	$courseList = array("courses" => $courses,
			"syncDateTime" => 0,
			"syncState" => false,
			"syncTimeOut" => $SYNC_TIMEOUT);
	logging("course list is ".json_encode($courseList));
	return $courseList;
}

?>
