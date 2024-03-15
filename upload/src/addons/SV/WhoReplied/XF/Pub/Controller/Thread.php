<?php

namespace SV\WhoReplied\XF\Pub\Controller;

use XF\Finder\User as UserFinder;
use XF\Mvc\ParameterBag;

class Thread extends XFCP_Thread
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\AbstractReply
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionWhoReplied(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
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

        $perPageChoices = \XF::options()->svWhoReplied_usersPerPageChoices ?? [40];
        if (empty($perPageChoices))
        {
            $perPageChoices = [40];
        }

        $perPage = $this->filter('per_page', 'uint');
        if (!in_array($perPage, $perPageChoices))
        {
            $perPage = reset($perPageChoices);
        }

        /** @var UserFinder $finder */
        $finder = $this->finder('XF:User')
                       ->with('ThreadUserPost|' . $threadId, true)
                       ->order("ThreadUserPost|$threadId.post_count", 'DESC')
                       ->order('user_id')
                       ->limitByPage($page, $perPage);
        $this->applyWhoRepliedFilters($finder, $filters);

        $total = $finder->total();
        $linkFilters = [];
        if (count($filters) !== 0)
        {
            $linkFilters['_xfFilter'] = $filters;
        }
        $this->assertValidPage(
            $page,
            $perPage,
            $total,
            'thread/who-replied',
            $linkFilters
        );

        $users = $finder->fetch();

        $finalUrl = $this->buildLink(
            'full:threads/who-replied',
            $thread,
            $linkFilters + ($page > 1 ? ['page' => $page] : [])
        );
        $addParamsToPageNav = $this->filter('_xfWithData', 'bool');

        $viewParams = [
            'thread' => $thread,
            'forum'  => $thread->Forum,
            'users'  => $users,

            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'perPageChoices' => $perPageChoices,

            'addParamsToPageNav' => $addParamsToPageNav,
            'linkFilters' => $linkFilters,

            'filter' => $filters,
            'finalUrl' => $finalUrl,
        ];
        return $this->view(
            'XF:Thread\WhoReplied',
            'whoreplied_list',
            $viewParams
        );
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

    protected function applyWhoRepliedFilters(UserFinder $finder, array &$filters): void
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
