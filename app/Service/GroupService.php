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
namespace App\Service;

use App\Constants\StatusConstant;
use App\Model\AclGroup;
use App\Model\User;
use App\Service\Dao\AclGroupDao;
use App\Service\Formatter\GroupFormatter;
use App\Service\Struct\Principal;
use Han\Utils\Service;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CachePut;
use Hyperf\Di\Annotation\Inject;

class GroupService extends Service
{
    #[Inject]
    protected AclGroupDao $dao;

    #[Inject]
    protected GroupFormatter $formatter;

    public function index(array $input, int $offset, int $limit)
    {
        [$total, $models] = $this->dao->find($input, $offset, $limit);

        $models->load('userModels');

        $result = $this->formatter->formatList($models);

        return [$total, $result];
    }

    /**
     * @param $input = [
     *     'name' => '',
     *     'principal' => 'self',
     *     'public_scope' => '1',
     *     'description' => '',
     *     'source_id' => 1,
     * ]
     */
    public function store(array $input, User $user)
    {
        $name = $input['name'];
        $principal = $input['principal'] ?? null;
        $scope = $input['public_scope'] ?? StatusConstant::SCOPE_PUBLIC;
        $description = $input['description'] ?? '';
        $sourceId = $input['source_id'] ?? null;
        $users = [];

        $principal = new Principal((string) $principal, $user);

        if ($sourceId) {
            $group = $this->dao->first($sourceId, true);
            $users = $group->users ?? [];
        }

        $model = new AclGroup();
        $model->name = $name;
        $model->principal = $principal->toArray();
        $model->public_scope = $scope;
        $model->description = $description;
        $model->users = $users;
        $model->save();

        return $this->formatter->detail($model);
    }

    #[Cacheable(prefix: 'group:all', ttl: 8640000)]
    public function getAll(): array
    {
        return $this->all();
    }

    #[CachePut(prefix: 'group:all', ttl: 8640000)]
    public function putAll(): array
    {
        return $this->all();
    }

    public function all(): array
    {
        $models = $this->dao->all();

        return $this->formatter->formatList($models);
    }
}
