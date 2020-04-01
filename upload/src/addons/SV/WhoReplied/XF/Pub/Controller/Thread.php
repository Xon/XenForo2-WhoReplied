<?php

namespace SV\WhoReplied\XF\Pub\Controller;

use XF\Finder\User as UserFinder;
use XF\Mvc\ParameterBag;

class Thread extends XFCP_Thread
{
    public function actionWhoReplied(ParameterBag $params)
    {
        $threadId = $params->get('thread_id');
        /** @var \SV\WhoReplied\XF\Entity\Thread $thread */
        $thread = $this->assertViewableThread($threadId, $this->getThreadViewExtraWith());

        if (!$thread->canViewWhoReplied())
        {
            return $this->noPermission();
        }

        $page = $this->filterPage($params->page);
        $perPage = \XF::options()->WhoReplied_usersPerPage;

        $linkFilters = [];

        $linkFilters['_xfFilter'] = $this->filter('_xfFilter', [
            'text'   => 'str',
            'prefix' => 'bool'
        ]);

        /** @var UserFinder $userFinder */
        $userFinder = $this->finder('XF:User');
        $userFinder->with("ThreadUserPost|{$threadId}", true);
        $userFinder->order("ThreadUserPost|{$threadId}.post_count", 'DESC');
        $userFinder->order('user_id');

        if (\utf8_strlen($linkFilters['_xfFilter']['text']))
        {
            $userFinder->where(
                $userFinder->columnUtf8('username'),
                'LIKE',
                $userFinder->escapeLike(
                    $linkFilters['_xfFilter']['text'],
                    $linkFilters['_xfFilter']['prefix'] ? '?%' : '%?%'
                )
            );
        }

        $userFinder->limitByPage($page, $perPage);

        $total = $userFinder->total();
        $users = $userFinder->fetch();

        $this->assertValidPage($page, $perPage, $total, 'thread/who-replied');

        $viewParams = [
            'thread' => $thread,
            'forum'  => $thread->Forum,
            'users'  => $users,

            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,

            'linkFilters' => $linkFilters
        ];

        return $this->view('XF:Thread\WhoReplied', 'whoreplied_list', $viewParams);
    }
}
