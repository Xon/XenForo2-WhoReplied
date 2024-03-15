<?php

namespace SV\WhoReplied;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $this->applyContentPermission('forum', 'whoRepliedView', 'forum', 'viewContent');
        $this->applyGlobalPermission('forum', 'whoRepliedView', 'forum', 'viewContent');
    }
}