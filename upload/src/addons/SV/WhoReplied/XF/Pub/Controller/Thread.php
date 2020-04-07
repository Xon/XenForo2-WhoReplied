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

        $filters = [];
        if ($this->request()->exists('_xfFilter'))
        {
            $filters = $this->filter('_xfFilter', [
                'text'   => 'str',
                'prefix' => 'bool',
                'page' => 'uint'
            ]);

            $page = $this->filterPage($filters['page']);
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

        if (\array_key_exists('text', $filters) && \utf8_strlen($filters['text']))
        {
            $userFinder->where(
                $userFinder->columnUtf8('username'),
                'LIKE',
                $userFinder->escapeLike(
                    $filters['text'],
                    $filters['prefix'] ? '?%' : '%?%'
                )
            );
        }

        $userFinder->limitByPage($page, $perPage);

        $total = $userFinder->total();
        $users = $userFinder->fetch();

        $this->assertValidPage($page, $perPage, $total, 'thread/who-replied');

        unset($filters['page']);

        $linkFilters = [];
        if (\array_key_exists('text', $filters))
        {
            $linkFilters['_xfFilter'] = $filters;
        }
        $finalUrl = $this->buildLink('full:threads/who-replied', $thread, $linkFilters);

        $addParamsToPageNav = $this->filter('_xfWithData', 'bool');

        $viewParams = [
            'thread' => $thread,
            'forum'  => $thread->Forum,
            'users'  => $users,

            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,

            'addParamsToPageNav' => $addParamsToPageNav,
            'linkFilters' => $linkFilters,

            'filter' => $filters,
            'finalUrl' => $finalUrl
        ];

        return $this->view('XF:Thread\WhoReplied', 'whoreplied_list', $viewParams);
    }
}
