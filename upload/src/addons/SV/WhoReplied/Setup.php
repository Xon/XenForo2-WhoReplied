<?php

namespace SV\WhoReplied;

use XF\Entity\User;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $db = $this->db();
        $db->query("
            insert ignore into xf_permission_entry_content (content_type, content_id, user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
            select distinct content_type, content_id, ?, 0, convert(permission_group_id using utf8), 'whoRepliedView', permission_value, permission_value_int
            from xf_permission_entry_content
            where permission_group_id = 'forum' and permission_id in ('viewContent')
        ", [User::GROUP_REG]);

        $db->query("
            insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
            select distinct ?, 0, convert(permission_group_id using utf8), 'whoRepliedView', permission_value, permission_value_int
            from xf_permission_entry
            where permission_group_id = 'forum' and permission_id in ('viewContent')
        ", [User::GROUP_REG]);
    }
}