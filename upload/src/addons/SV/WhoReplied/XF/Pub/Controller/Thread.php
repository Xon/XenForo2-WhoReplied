<?php

namespace SV\WhoReplied\XF\Pub\Controller;

/*
 * Extends \XF\Pub\Controller\Thread
 */
use XF\Mvc\ParameterBag;

class Thread extends XFCP_Thread
{
	public function actionWhoReplied(ParameterBag $params)
	{
        $threadId = $params->get('thread_id');
        $thread = $this->assertViewableThread($threadId, $this->getThreadViewExtraWith());

		if (!\XF::visitor()->hasNodePermission($thread->node_id, 'whoRepliedView')) {
			return $this->noPermission();
		}

		$criteria = $this->filter('criteria', 'array');
		$order = $this->filter('order', 'str');
		$direction = $this->filter('direction', 'str');

		$page = isset($params['page']) ? $params['page'] : 1;
		$perPage = \XF::options()['WhoReplied_usersPerPage'];

		$filter = $this->filter('_xfFilter', [
			'text' => 'str',
			'prefix' => 'bool'
		]);
		$searcher = $this->searcher('XF:User', $criteria);

		if ($order && !$direction)
		{
			$direction = $searcher->getRecommendedOrderDirection($order);
		}

		$searcher->setOrder($order, $direction);

		$replyingUserIds = join(
			',',
			array_column(
				$this->finder('XF:ThreadUserPost')
					->where('thread_id', '=', $threadId)
					->fetchColumns('user_id'),
				'user_id'
			)
		);

		$finder = $searcher->getFinder();
		$finder->whereSql("user_id IN ($replyingUserIds)");

		if (strlen($filter['text']))
		{
			$finder->where(
				'username',
				'LIKE',
				$finder->escapeLike($filter['text'], $filter['prefix'] ? '?%' : '%?%')
			);
		} else {
			$finder->limitByPage($page, $perPage);
		}

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
			'sortOptions' => $searcher->getOrderOptions(),
			'order' => $order,
			'direction' => $direction
		];
		return $this->view('XF:Thread\WhoReplied', 'whoreplied_list', $viewParams);
	}
}
