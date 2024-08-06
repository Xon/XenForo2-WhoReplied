<?php

namespace SV\WhoReplied\XF\Pub\Controller;

use SV\StandardLib\Helper;
use XF\Finder\User as UserFinder;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Exception as ReplyException;
use function count;
use function in_array;
use function reset;
use function strlen;

/**
 * @extends \XF\Pub\Controller\Thread
 */
class Thread extends XFCP_Thread
{
    /**
     * @param ParameterBag $params
     * @return AbstractReply
     * @throws ReplyException
     */
    public function actionWhoReplied(ParameterBag $params): AbstractReply
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

        $default = [25,50];
        $perPageChoices = \XF::options()->svWhoReplied_usersPerPageChoices ?? null;
        if (!is_array($perPageChoices) || count($perPageChoices) === 0)
        {
            $perPageChoices = $default;
        }

        $perPage = (int)$this->filter(
            'per_page',
            'uint',
            $this->request->getCookie('svWhoReplied_per_page') ?? reset($default)
        );
        if (!in_array($perPage, $perPageChoices, true))
        {
            $perPage = reset($perPageChoices);
        }

//        if ($this->filter('_xfWithData', 'bool'))
//        {
//            \XF::dumpSimple($this->filter('per_page', 'uint'));
//            die();
//        }

        $finder = Helper::finder(UserFinder::class)
                        ->with('Profile', true)
                        ->with('Option', true)
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

        if ($perPage !== reset($perPageChoices))
        {
            $linkFilters['per_page'] = $perPage;
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
                'text'     => 'str',
                'prefix'   => 'bool'
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
