<?php
/**
 *
 */
class QTIService extends VLEService
{
    public static function apiDefinition($prefix, $link="qti.php", $name="")
    {
        return parent::apiDefinition($prefix, $link, "powertla.content.imsqti");
    }

    protected function initializeRun()
    {
        $this->VLE->getSEssionValidator()->rejectTokenType("Client");
    }

    protected function findOperation($method, $path)
    {
        $ops = array("course", "questionpool");
        $cop = count($ops);
        $cnt = (empty($path) ? 0 : count($path));


        if ($cnt > 0)
        {
            $cnt = $cnt < $cop ? $cnt : $cop;
            return $method . '_' . $ops[$cnt - 1];
        }

        return $method;
    }

    protected function get_course()
    {
        $this->data = array();

        $cbH = $this->VLE->getQTIPoolBroker();
        if ($cbH)
        {
            $this->data = $cbH->getPoolList($this->path_info[0]);
        }
    }

    protected function get_questionpool()
    {
        $this->data = array();

        $cbH = $this->VLE->getQTIPoolBroker();
        if ($cbH)
        {
            $this->data = $cbH->getSinglePool($this->path_info[0], $this->path_info[1]);
        }
    }
}
