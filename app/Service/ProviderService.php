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

use App\Constants\Permission;
use App\Model\ConfigPriority;
use App\Model\ConfigPriorityProperty;
use App\Model\ConfigResolution;
use App\Model\ConfigResolutionProperty;
use App\Model\ConfigScreen;
use App\Model\ConfigState;
use App\Service\Context\GroupContext;
use App\Service\Dao\ConfigPriorityDao;
use App\Service\Dao\ConfigPriorityPropertyDao;
use App\Service\Dao\ConfigResolutionDao;
use App\Service\Dao\ConfigResolutionPropertyDao;
use App\Service\Dao\ConfigStateDao;
use App\Service\Dao\ConfigStatePropertyDao;
use App\Service\Dao\ConfigTypeDao;
use App\Service\Dao\UserDao;
use App\Service\Dao\UserGroupProjectDao;
use App\Service\Dao\VersionDao;
use App\Service\Formatter\UserFormatter;
use Han\Utils\Service;
use Hyperf\Database\Model\Collection;
use JetBrains\PhpStorm\ArrayShape;
use function Han\Utils\sort;

/**
 * TODO: 不懂为什么叫这个名字.
 */
class ProviderService extends Service
{
    public function getUserList(string $key): array
    {
        $projects = di()->get(UserGroupProjectDao::class)->findByProjectKey($key);

        $userIds = [];
        $groupIds = [];
        foreach ($projects as $project) {
            $project->isGroup() ? $groupIds[] = $project->ug_id : $userIds[] = $project->ug_id;
        }

        if ($groupIds) {
            $groups = GroupContext::instance()->find($groupIds);
            foreach ($groups as $group) {
                $userIds = array_merge($userIds, $group->users);
            }
        }

        $userIds = array_values(array_unique($userIds));

        $models = di()->get(UserDao::class)->findMany($userIds);

        return di()->get(UserFormatter::class)->formatSmalls($models);
    }

    public function getAssignedUsers(string $key)
    {
        $userIds = di()->get(AclService::class)->getUserIdsByPermission(Permission::ISSUE_ASSIGNED, $key);

        $models = di()->get(UserDao::class)->findMany($userIds);

        return di()->get(UserFormatter::class)->formatSmalls($models);
    }

    public function getStateListOptions(string $key): array
    {
        $states = $this->getStateList($key);
        $result = [];
        foreach ($states as $state) {
            $result[] = [
                'id' => $state->key ?: $state->id,
                'name' => trim($state->name),
                'category' => $state->category,
            ];
        }

        return $result;
    }

    public function getStateList(string $key): Collection
    {
        $states = di()->get(ConfigStateDao::class)->findOrByProjectKey($key);

        $property = di()->get(ConfigStatePropertyDao::class)->firstByProjectKey($key);
        if ($sequence = $property?->sequence) {
            $sequence = array_flip($sequence);
            $result = sort($states, static function (ConfigState $model) use ($sequence) {
                return -($sequence[$model->id] ?? 999);
            })->toArray();

            $states = new Collection($result);
        }

        return $states;
    }

    #[ArrayShape([Collection::class, ConfigResolutionProperty::class])]
    public function getResolutionList(string $key): array
    {
        $resolutions = di()->get(ConfigResolutionDao::class)->findOrByProjectKey($key);
        $property = di()->get(ConfigResolutionPropertyDao::class)->firstByProjectKey($key);
        if ($sequence = $property?->sequence) {
            $sequence = array_flip($sequence);
            $result = sort($resolutions, static function (ConfigResolution $model) use ($sequence) {
                return -($sequence[$model->id] ?? 999);
            })->toArray();

            $resolutions = new Collection($result);
        }

        return [$resolutions, $property];
    }

    #[ArrayShape([Collection::class, ConfigPriorityProperty::class])]
    public function getPriorityList(string $key)
    {
        $priorities = di()->get(ConfigPriorityDao::class)->findOrByProjectKey($key);

        $property = di()->get(ConfigPriorityPropertyDao::class)->firstByProjectKey($key);

        if ($sequence = $property?->sequence) {
            $sequence = array_flip($sequence);
            $result = sort($priorities, static function (ConfigPriority $model) use ($sequence) {
                return -($sequence[$model->id] ?? 999);
            })->toArray();

            $priorities = new Collection($result);
        }

        return [$priorities, $property];
    }

    public function getPriorityOptions(string $key)
    {
        [$priorities, $property] = $this->getPriorityList($key);

        $options = [];
        /** @var ConfigPriority $priority */
        foreach ($priorities as $priority) {
            $item = [
                'id' => $priority->key ?: $priority->id,
                'name' => trim($priority->name ?? ''),
                'color' => $priority->color,
            ];

            if ($property?->default_value == $priority->id) {
                $item['default'] = true;
            }

            $options[] = $item;
        }
        return $options;
    }

    public function getResolutionOptions(string $key)
    {
        [$resolutions, $property] = $this->getResolutionList($key);
        $options = [];
        /** @var ConfigResolution $resolution */
        foreach ($resolutions as $resolution) {
            $item = [
                'id' => $resolution->key ?: $resolution->id,
                'name' => trim($resolution->name ?? ''),
            ];

            if ($property?->default_value == $resolution->id) {
                $item['default'] = true;
            }

            $options[] = $item;
        }
        return $options;
    }

    public function getVersionList(string $key)
    {
        $versions = di()->get(VersionDao::class)->findByProjectKey($key);

        return $versions->columns(['name'])->toArray();
    }

    public function getSchemaByType(int $typeId): array
    {
        $type = di()->get(ConfigTypeDao::class)->first($typeId, false);
        if (! $type) {
            return [];
        }

        return $this->getScreenSchema($type->project_key, $typeId, $type->screen);
    }

    public function getScreenSchema(string $key, int $typeId, ConfigScreen $screen)
    {
        $new_schema = [];
        $versions = null;
        $users = null;
        foreach ($screen->schema ?: [] as $key => $val) {
            if (isset($val['applyToTypes'])) {
                if ($val['applyToTypes'] && ! in_array($type_id, explode(',', $val['applyToTypes'] ?: ''))) {
                    continue;
                }
                unset($val['applyToTypes']);
            }

            if ($val['key'] == 'assignee') {
                $users = self::getAssignedUsers($project_key);
                foreach ($users as $key => $user) {
                    $users[$key]['name'] = $user['name'] . '(' . $user['email'] . ')';
                }
                $val['optionValues'] = self::pluckFields($users, ['id', 'name']);
            } elseif ($val['key'] == 'resolution') {
                $resolutions = self::getResolutionOptions($project_key);
                $val['optionValues'] = self::pluckFields($resolutions, ['_id', 'name']);
                foreach ($resolutions as $key2 => $val2) {
                    if (isset($val2['default']) && $val2['default']) {
                        $val['defaultValue'] = $val2['_id'];
                        break;
                    }
                }
            } elseif ($val['key'] == 'priority') {
                $priorities = self::getPriorityOptions($project_key);
                $val['optionValues'] = self::pluckFields($priorities, ['_id', 'name']);
                foreach ($priorities as $key2 => $val2) {
                    if (isset($val2['default']) && $val2['default']) {
                        $val['defaultValue'] = $val2['_id'];
                        break;
                    }
                }
            } elseif ($val['key'] == 'module') {
                $modules = self::getModuleList($project_key);
                $val['optionValues'] = self::pluckFields($modules, ['_id', 'name']);
            } elseif ($val['key'] == 'epic') {
                $epics = self::getEpicList($project_key);
                $val['optionValues'] = self::pluckFields($epics, ['_id', 'name', 'bgColor']);
            } elseif ($val['key'] == 'labels') {
                $labels = self::getLabelOptions($project_key);
                $couple_labels = [];
                foreach ($labels as $label) {
                    $couple_labels[] = ['id' => $label['name'], 'name' => $label['name']];
                }
                $val['optionValues'] = $couple_labels;
            } elseif ($val['type'] == 'SingleVersion' || $val['type'] == 'MultiVersion') {
                $versions === null && $versions = self::getVersionList($project_key);
                $val['optionValues'] = self::pluckFields($versions, ['_id', 'name']);
            } elseif ($val['type'] == 'SingleUser' || $val['type'] == 'MultiUser') {
                $users === null && $users = self::getUserList($project_key);
                foreach ($users as $key => $user) {
                    $users[$key]['name'] = $user['name'] . '(' . $user['email'] . ')';
                }
                $val['optionValues'] = self::pluckFields($users, ['id', 'name']);
            }

            if (isset($val['_id'])) {
                unset($val['_id']);
            }

            $new_schema[] = $val;
        }

        return $new_schema;
    }
}