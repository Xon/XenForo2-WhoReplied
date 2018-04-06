<?php

namespace SV\WhoReplied\XF\Entity;

/**
 * Extends \XF\Entity\Thread
 */
class Thread extends XFCP_Thread
{
    /**
     * @param string|null $error
     * @return bool
     */
    public function canViewWhoReplied(/** @noinspection PhpUnusedParameterInspection */
        $error = null)
    {
        $visitor = \XF::visitor();

        return $visitor->hasNodePermission($this->node_id, 'whoRepliedView');
    }
}