<?php

namespace SV\WhoReplied\XF\Entity;

/**
 * Extends \XF\Entity\Thread
 */
class Thread extends XFCP_Thread
{
    /**
     * @param \XF\Phrase|string|null $error
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function canViewWhoReplied($error = null): bool
    {
        $visitor = \XF::visitor();

        return $visitor->hasNodePermission($this->node_id, 'whoRepliedView');
    }
}