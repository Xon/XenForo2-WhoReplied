<?php

namespace SV\WhoReplied\XF\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\User
 */
class User extends XFCP_User
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->relations['ThreadUserPost'] = [
            'entity'     => 'XF:ThreadUserPost',
            'type'       => self::TO_MANY,
            'conditions' => 'user_id',
            'key'        => 'thread_id',
        ];

        return $structure;
    }
}