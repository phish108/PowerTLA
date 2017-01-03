<?php

namespace PowerTLA\Model\Identity\Webfinger;

class Ilias extends \PowerTLA\Model\Identity\Webfinger
{
    protected function getSystemId() {
        // FIXME use the client name
        return $_SERVER["SERVER_NAME"];
    }

    public function getUsername($useridList) {
        return null;
    }
    public function findSubjectByEMail($email) {
        return 0;
    }
    public function findSubjectByAcct($acct) {
        return 0;
    }
    public function findSubjectByUserId($userId) {
        return 0;
    }
    public function findSubjectByOpenId($openIdUri) {
        return 0;
    }
    public function findSubjectByHomepage($homepageUri) {
        return 0;
    }
    // public function findAcctByUserContext($userid, $context);

    protected function getSubjectProperties() {
        return [];
    }
    protected function getSubjectProfile() {
        return [];
    }

    protected function hasSharedContext($useridList) {
        return false;
    }

    protected function loadContextAliases($otherUserId, $exclude) {
        return $this->loadAliases($exclude);
    }

    protected function loadAliases($exclude) {
        $retval = [];

        return $retval;
    }
}

?>
