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

        $filters = $this->getWhoRepliedFilters();
        $page = $this->filterPage($params['page'] ?? 0);
        $perPage = (int)(\XF::options()->WhoReplied_usersPerPage ?? 40);

        /** @var UserFinder $finder */
        $finder = $this->finder('XF:User')
                       ->with("ThreadUserPost|{$threadId}", true)
                       ->order("ThreadUserPost|{$threadId}.post_count", 'DESC')
                       ->order('user_id')
                       ->limitByPage($page, $perPage);
        $this->applyWhoRepliedFilters($finder, $filters);

        $total = $finder->total();
        $linkFilters = [];
        if (count($filters) !== 0)
        {
            $linkFilters['_xfFilter'] = $filters;
        }
        $this->assertValidPage($page, $perPage, $total, 'thread/who-replied', $linkFilters);

        $users = $finder->fetch();

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
            'finalUrl' => $finalUrl,
        ];

        return $this->view('XF:Thread\WhoReplied', 'whoreplied_list', $viewParams);
    }

    protected function getWhoRepliedFilters(): array
    {
        if ($this->request()->exists('_xfFilter'))
        {
            return $this->filter('_xfFilter', [
                'text'   => 'str',
                'prefix' => 'bool',
            ]);
        }

        return [];
    }

    protected function applyWhoRepliedFilters(UserFinder $finder, array &$filters)
    {
        if (strlen($filters['text'] ?? '') !== 0)
        {
            $hasPrefixSearch = (bool)($filters['prefix']  ?? true);
            if (!$hasPrefixSearch)
            {
                unset($filters['prefix']);
            }

            $finder->where(
                $finder->columnUtf8('username'),
                'LIKE',
                $finder->escapeLike(
                    $filters['text'],
                    $hasPrefixSearch ? '?%' : '%?%'
                )
            );
        }
        else
        {
            unset($filters['text']);
            unset($filters['prefix']);
        }
    }
}
