<?php

namespace SV\WhoReplied\XF\Entity;

use XF\Entity\ThreadUserPost;
use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\User
 *
 * @property ThreadUserPost[] ThreadUserPost
 */
class User extends XFCP_User
{
    /**
     * @param Structure $structure
     * @return Structure
     * @noinspection PhpMissingReturnTypeInspection
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