<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Listener;

use App\Event\IssueEvent;
use App\Service\IssueService;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
class IssueUpdateListener implements ListenerInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            IssueEvent::class,
        ];
    }

    /**
     * @param IssueEvent $event
     */
    public function process(object $event)
    {
        $issue = $event->getIssue();
        // 同步到搜索引擎
        di()->get(IssueService::class)->pushToSearch($issue->id);
    }
}