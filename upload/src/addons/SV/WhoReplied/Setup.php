<?php

namespace SV\WhoReplied;

use SV\StandardLib\Helper;
use SV\StandardLib\Option\EntriesPerPage;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Entity\Option as OptionEntity;
use function array_unique;

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

    public function upgrade2030000Step1(): void
    {
        $usersPerPage = (int)(\XF::options()->WhoReplied_usersPerPage ?? 0);
        if ($usersPerPage === 0 || $usersPerPage === 50)
        {
            return;
        }

        $option = Helper::find(OptionEntity::class, 'svWhoReplied_usersPerPageChoices');
        if ($option === null)
        {
            $option = Helper::createEntity(OptionEntity::class);
            $option->option_id = 'svWhoReplied_usersPerPageChoices';
            $option->data_type = 'array';
            $option->sub_options = ['*'];
            $option->edit_format = 'callback';
            $option->edit_format_params = 'SV\WhoReplied\Option\UsersPerPage::renderOption';
            $option->option_value = [25,50];
            $option->setOption('verify_validation_callback', false);
            $option->setOption('verify_value', false);
            $option->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', false);
            $option->save();
            // required so the verify function will work later
            $option->validation_class = EntriesPerPage::class;
            $option->validation_method = 'verifyOption';
            $option->save();
        }
        $values = $option->option_value;
        $max = max($values);
        if ($max > $usersPerPage)
        {
            $option->option_value = [$usersPerPage];
        }
        else
        {
            $values[] = $usersPerPage;
            $values = array_unique($values);
            sort($values);
            $option->option_value = $values;
        }

        $option->save();
    }
}