<?php

namespace SV\WhoReplied\Option;

use XF\Option\AbstractOption;
use function count;
use function is_int;
use function is_string;
use function strlen;
use function strval;

class UsersPerPage extends AbstractOption
{
    public static function renderOption(\XF\Entity\Option $option, array $htmlParams): string
    {
        $choices = [];
        foreach ($option->option_value AS $perPage)
        {
            $choices[] = [
                'value' => $perPage,
            ];
        }

        return self::getTemplate(
            'admin:option_template_svWhoReplied_usersPerPageChoices',
            $option,
            $htmlParams,
            [
                'choices' => $choices,
                'nextCounter' => count($choices)
            ]
        );
    }

    public static function verifyOption(array &$value): bool
    {
        $output = [];

        foreach ($value AS $perPage)
        {
            if (empty($perPage['value']))
            {
                continue;
            }

            $output[] = $perPage['value'];
        }

        sort($output);

        $value = $output;

        return true;
    }
}
