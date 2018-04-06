<?php

namespace SV\WhoReplied\XF\Pub\Controller;

/*
 * Extends \XF\Pub\Controller\Thread
 */
use XF\Mvc\Entity\FinderExpression;
use XF\Mvc\ParameterBag;

class Thread extends XFCP_Thread
{
	public function actionWhoReplied(ParameterBag $params)
	{
        $threadId = $params->get('thread_id');
        /** @var \SV\WhoReplied\XF\Entity\Thread $thread */
        $thread = $this->assertViewableThread($threadId, $this->getThreadViewExtraWith());

		if (!$thread->canViewWhoReplied()) {
			return $this->noPermission();
		}

		$criteria = $this->filter('criteria', 'array');
        //$secondaryOrder = $this->filter('order', 'str');
        //$secondaryDirection = $this->filter('direction', 'str');

		$page = isset($params['page']) ? $params['page'] : 1;
		$perPage = \XF::options()['WhoReplied_usersPerPage'];

		$filter = $this->filter('_xfFilter', [
			'text' => 'str',
			'prefix' => 'bool'
		]);
		$searcher = $this->searcher('XF:User', $criteria);

		$finder = $searcher->getFinder();
        $finder->with("ThreadUserPost|{$threadId}", true);
        $finder->order("ThreadUserPost|{$threadId}.post_count", 'DESC');
        $finder->order('user_id');

		if (strlen($filter['text']))
		{
			$finder->where(
				'username',
				'LIKE',
				$finder->escapeLike($filter['text'], $filter['prefix'] ? '?%' : '%?%')
			);
		}
		$finder->limitByPage($page, $perPage);

		$total = $finder->total();
		$users = $finder->fetch();

		$this->assertValidPage($page, $perPage, $total, 'thread/who-replied');


		$viewParams = [
			'thread' => $thread,
			'forum' => $thread->Forum,
			'users' => $users,

			'total' => $total,
			'page' => $page,
			'perPage' => $perPage,

			'criteria' => $searcher->getFilteredCriteria(),
			'filter' => $filter['text'],
			'sortOptions' => [],
			'order' => '',
			'direction' => ''
		];
		return $this->view('XF:Thread\WhoReplied', 'whoreplied_list', $viewParams);
	}
}
