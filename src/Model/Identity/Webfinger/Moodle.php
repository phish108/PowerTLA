<?php

namespace PowerTLA\Model\Identity\Webfinger;

class Moodle extends \PowerTLA\Model\Identity\Webfinger
{
    protected function getSystemId() {
        global $CFG;

        $url = parse_url($CFG->wwwroot, PHP_URL_HOST);
        if (!empty($url)) {
             return $url;
        }
        return $_SERVER["SERVER_NAME"];
    }

    public function findSubjectByAcct($acct) {
        if (!$this->findUserResource(["username" => $acct])) {
            $aAcct = explode("@", $acct, 2);
            if (count($aAcct) == 2 && $aAcct[1] == $this->getSystemId()) {
                $this->findUserResource(["username" => $aAcct[0]]);
            }
        }
        return $this->userid;
    }

    public function findSubjectByEMail($email) {
        return $this->findUserResource(["mail" => $email]);
    }

    public function findSubjectByUserId($userid) {
        return $this->findUserResource(["id" => $userid]);
    }

    public function findSubjectByOpenId($openIdUri) {
        // FIXME use the correct openIdUri
        return $this->findUserResource(["url" => $openIdUri]);
    }

    public function findSubjectByHomepage($homepageUri) {
        return $this->findUserResource(["url" => $homepageUri]);
    }

    private function findUserResource($attr) {
        global $DB;
        $this->clear();

        $user = $DB->get_record("user", $attr);

        if ($user && !($user->deleted || $user->suspended)) {
            if (!strpos($user->username, "@")) {
                $user->username = $user->username . "@" . $this->getSystemId();
            }
            $this->resource = $user;
            $this->userid   = $user->id;
            $this->acct = $user->username;
        }
        return $this->userid;
    }

    public function getUsername($useridList) {
        global $DB;
        if (!is_array($useridList)) {
            $useridList = [$useridList];
        }

        if ($this->resource && in_array($this->resource->id, $useridList)) {
            return "acct:" . $this->resource->username;
        }

        // NOTE: the user id list is typically 1 item long, so this does not
        // NOTE: cause much harm.
        foreach ($useridList as $userid) {
            if ($this->findUserResource(["id" => $userid])) {
                return "acct:" . $this->resource->username;
            }
        }
        return null;
    }

    protected function hasSharedContext($useridList) {
        if ($this->getUsername($useridList) !== null) {
            return true;
        }
        return false;
    }

    protected function getSubjectProperties(){
        global $DB;

        $retval = [];
        if ($this->resource && $this->resource->id) {
            if ($props = $DB->get_records("user_preferences", ["userid" => $this->resource->id])) {
                foreach ($props as $property) {
                    $attrVals = get_object_vars($property);
                    $retval[$property->name] = $property->value;
                }
            }

            $attrVals = get_object_vars($this->resource);
            foreach (["lang", "theme", "timezone"] as $key) {
                $retval[$key] = $attrVals[$key];
            }
        }
        return $retval;
    }

    protected function getSubjectProfile(){
        $retval = [];
        if ($this->resource) {
            $attrVals = get_object_vars($this->resource);
            foreach (["firstname", "lastname", "middlename"] as $key) {
                switch ($key) {
                    case "firstname":
                        $retval["givenname"] = $attrVals[$key];
                        break;
                    case "lastname":
                        $retval["familyname"] = $attrVals[$key];
                        break;
                    default:
                        $retval[$key] = $attrVals[$key];
                        break;
                }
            }
        }
        return $retval;
    }

    protected function loadContextAliases($otherUserId, $exclude) {
        return $this->loadAliases($exclude);
    }

    protected function loadAliases($exclude) {
        $retval = [];
        if ($this->resource) {
            $attrVals = get_object_vars($this->resource);
            foreach (["mail", "username", "url"] as $key) {
                if ($attrVals[$key] != $exclude) {
                    $retval[] = $attrVals[$key];
                }
            }
        }
        return $retval;
    }
}

?>
