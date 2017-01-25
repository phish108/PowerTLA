<?php

namespace PowerTLA\Model\Content;

class Course
{
    public function getCourses($input) {

    }
    public function getCourseContents($input) {

    }
    public function getCourseParticipants($input) {

    }
    public function enrolCourseParticipant($input) {

    }

    /**
     * Returns the pathmap of the model.
     *
     * This is automatically generated from the API specification. You can
     * safely ignore this part.
     *
     * Note: on API changes, this method may change too.
     */
    final public function getPathMap() {
        return array (
              0 =>
              array (
                'pattern' => '/^\\/([^\\/]+)\\/participants(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'getCourseParticipants',
                    'produces' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      200 =>
                      array (
                        'description' => 'Successful response',
                      ),
                      403 =>
                      array (
                        'description' => 'User is not enrolled in the course',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'courseId',
                        'in' => 'path',
                        'description' => 'the official courseId that is exposed in System URLs.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                  'put' =>
                  array (
                    'operationId' => 'enrolCourseParticipant',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      200 =>
                      array (
                        'description' => 'Successful response',
                      ),
                      403 =>
                      array (
                        'description' => 'User is not allowed to entrol users into the course',
                      ),
                      404 =>
                      array (
                        'description' => 'User account does not exist',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'courseId',
                        'in' => 'path',
                        'description' => 'The official courseId that is exposed in System URLs.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                  0 => 'courseId',
                ),
                'path' => '/{courseId}/participants',
              ),
              1 =>
              array (
                'pattern' => '/^\\/([^\\/]+)\\/contents(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'getCourseContents',
                    'produces' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      200 =>
                      array (
                        'description' => 'Successful response',
                      ),
                      403 =>
                      array (
                        'description' => 'User has no access to the course contents',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'courseId',
                        'in' => 'path',
                        'description' => 'the official courseId that is exposed in System URLs.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                  0 => 'courseId',
                ),
                'path' => '/{courseId}/contents',
              ),
              2 =>
              array (
                'pattern' => '/^\\/(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'getCourses',
                    'produces' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      200 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/',
              ),
            );
    }

    /**
     * Returns the version of the API spec
     */
    final public function getVersion() {
        return '1.0.0';
    }

    /**
     * Returns the rsd protocol of the API
     */
    final public function getProtocol() {
        return 'ch.xapi.protocols.courses';
    }
}

?>
