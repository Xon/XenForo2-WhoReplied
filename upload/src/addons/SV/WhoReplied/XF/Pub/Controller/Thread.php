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

        $linkFilters = [];

        if ($this->request()->exists('_xfFilter'))
        {
            $linkFilters['_xfFilter'] = $this->filter('_xfFilter', [
                'text'   => 'str',
                'prefix' => 'bool',
                'page' => 'uint'
            ]);

            $page = $this->filterPage($linkFilters['_xfFilter']['page']);
        }
        else
        {
            $page = $this->filterPage($params->page);
        }
        $perPage = \XF::options()->WhoReplied_usersPerPage;

        /** @var UserFinder $userFinder */
        $userFinder = $this->finder('XF:User');
        $userFinder->with("ThreadUserPost|{$threadId}", true);
        $userFinder->order("ThreadUserPost|{$threadId}.post_count", 'DESC');
        $userFinder->order('user_id');

        if (\array_key_exists('_xfFilter', $linkFilters) && \utf8_strlen($linkFilters['_xfFilter']['text']))
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

        $tmpLinkFilters = $linkFilters;
        if (\array_key_exists('_xfFilter', $tmpLinkFilters))
        {
            unset($tmpLinkFilters['_xfFilter']['page']);
            $tmpLinkFilters['page'] = $page;
        }
        $finalUrl = $this->buildLink('full:threads/who-replied', $thread, $tmpLinkFilters);
        unset($tmpLinkFilters);

        $viewParams = [
            'thread' => $thread,
            'forum'  => $thread->Forum,
            'users'  => $users,

            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,

            'linkFilters' => $linkFilters,
            'finalUrl' => $finalUrl
        ];

        return $this->view('XF:Thread\WhoReplied', 'whoreplied_list', $viewParams);
    }
}
