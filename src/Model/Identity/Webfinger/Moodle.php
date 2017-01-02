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

    public function findSubjectByUserId($userid){
        global $DB;
        $context = null;
        if (!empty($this->context)) {
            $context = $json_encode($this->context);
        }
        if ($user = $DB->get_record('pwrtla_wf_users',
                                    array("userid" => $userid, "context" => $context))) {

            $this->userid = $user->userid;
            $this->acct = $user->acct;
            if (!empty($user->context)) {
                $this->context = json_decode($user->context, true);
            }
        }
        else {
            // no user wih
        }
    }
    public function findSubjectByEMail($email){
        global $DB;

        if ($user = $DB->get_record('user',
                                    array("mail" => $email))) {
            $this->findSubjectByUserId($user->id);
        }
    }

    public function findSubjectByAcct($acctUri) {
        global $DB;

        $acct = array_pop(explode(":", $acct));

        if ($user = $DB->get_record('pwrtla_wf_users',
                                    array("acct" => $acct))) {

            $this->userid = $user->userid;
            $this->acct = $user->acct;
            if (!empty($user->context)) {
                $this->context = json_decode($user->context, true);
            }
        }
    }
    public function findSubjectByOpenId($openIdUri){
        global $DB;

    }

    public function findSubjectByHomepage($homepageUri){
        global $DB;
        if ($user = $DB->get_record('user',
                                    array("url" => $homepageUri)))
        {
            $this->findSubjectByUserId($user->id);
        }
    }

    public function findAcctByUserContext($userid, $context){
        global $DB;

    }
    public function findAcctByUserRegistration($userid, $registration){
        global $DB;

    }

    protected function getSubjectPreferences(){
        global $DB;

    }
    protected function getSubjectProfile(){
        global $DB;

    }

    protected function hasSharedContext($userid){
        global $DB;

    }
    protected function loadUserProfile($userid){
        global $DB;

    }
    protected function loadAliases() {
        global $DB;

        $retval = [];
        if ($this->userid) {
            $records = $DB->get_records('pwrtla_wf_users',
                                        array("userid" => $userid));
            foreach ($records as $alias) {
                if ($alias->acct !== $this->acct) {
                    $retval[] = $alias->acct;
                }
            }
        }
        return $retval;
    }

    protected function loadContextAliases($users) {
        // all aliases to the same profile within your contexts
    }

    /**
 	 * Creates a new acct instance using the member properties.
 	 *
 	 * @return void
	 */
	protected function storeAcct(){}

    /**
 	 * loads the acct for the given condition.
     * if an acct has aliases (that share a context), this will populate the aliases as well
 	 *
 	 * @param type
 	 * @return void
	 */
	protected function loadAcct(){}
}

?>
