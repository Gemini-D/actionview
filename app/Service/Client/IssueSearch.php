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
namespace App\Service\Client;

use App\Constants\StatusConstant;
use App\Model\Issue;
use App\Service\Formatter\IssueFormatter;
use Han\Utils\ElasticSearch;
use Hyperf\Database\Model\Model;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;

class IssueSearch extends ElasticSearch
{
    public function mapping(): array
    {
        return [
            'id' => ['type' => 'long'],
            'no' => ['type' => 'long'],
            'project_key' => ['type' => 'keyword'],
            'type' => ['type' => 'long'],
            'state' => ['type' => 'keyword'],
            'parent_id' => ['type' => 'long'],
            'del_flg' => ['type' => 'byte'],
            'resolution' => ['type' => 'keyword'],
            'priority' => ['type' => 'keyword'],
            'resolve_version' => ['type' => 'long'],
            'labels' => ['type' => 'keyword'],
            'epic' => ['type' => 'keyword'],
            'module' => ['type' => 'keyword'],
            'assignee' => [
                'properties' => [
                    'id' => ['type' => 'long'],
                    'name' => ['type' => 'text'],
                    'email' => ['type' => 'text'],
                ],
            ],
            'reporter' => [
                'properties' => [
                    'id' => ['type' => 'long'],
                    'name' => ['type' => 'text'],
                    'email' => ['type' => 'text'],
                ],
            ],
            'modifier' => [
                'properties' => [
                    'id' => ['type' => 'long'],
                    'name' => ['type' => 'text'],
                    'email' => ['type' => 'text'],
                ],
            ],
            'created_at' => ['type' => 'date'],
            'updated_at' => ['type' => 'date'],
        ];
    }

    public function index(): string
    {
        return 'actionview_issue';
    }

    public function type(): string
    {
        return 'doc';
    }

    public function findOrVersion(int $version, array $keys, int $size = 100)
    {
        $search = new Search();
        $bool = new BoolQuery();
        $bool->add(new TermQuery('del_flg', StatusConstant::DELETED), BoolQuery::MUST_NOT);
        foreach ($keys as $key) {
            $bool->add(new TermQuery($key, $version), BoolQuery::SHOULD);
        }
        $query = $search->addQuery($bool)->setSize(100)->toArray();

        return $this->search($query);
    }

    public function countByVersion(array $versions): array
    {
        if (empty($versions)) {
            return [];
        }

        $bool = [];
        $bool['must'][] = ['term' => ['del_flg' => StatusConstant::NOT_DELETED]];
        $bool['must'][] = ['terms' => ['resolve_version' => $versions]];

        $unresolved = $bool;
        $unresolved['must'][] = ['term' => ['resolution' => StatusConstant::STATUS_UNRESOLVED]];

        $client = $this->client();
        $body = [
            'aggs' => [
                'cnt' => [
                    'filter' => ['bool' => $bool],
                    'aggs' => [
                        'group_by_version' => [
                            'terms' => ['field' => 'resolve_version'],
                        ],
                    ],
                ],
                'unresolved_cnt' => [
                    'filter' => ['bool' => $unresolved],
                    'aggs' => [
                        'group_by_version' => [
                            'terms' => ['field' => 'resolve_version'],
                        ],
                    ],
                ],
            ],
            'size' => 0,
        ];
        $params = [
            'index' => $this->index(),
            'type' => $this->type(),
            'body' => $body,
        ];

        $res = $client->search($params);
        $result = [];
        foreach ($versions as $version) {
            $result[$version] = [
                'unresolved_cnt' => 0,
                'cnt' => 0,
            ];
        }

        foreach (['unresolved_cnt', 'cnt'] as $key) {
            foreach ($res['aggregations'][$key]['group_by_version']['buckets'] ?? [] as $bucket) {
                $result[$bucket['key']][$key] = $bucket['doc_count'];
            }
        }

        return $result;
    }

    /**
     * 根据模型获取对应document，如果数据字段不一致，请重写此方法.
     * @param Issue $model
     */
    protected function document(Model $model): array
    {
        return di()->get(IssueFormatter::class)->base($model);
    }
}
