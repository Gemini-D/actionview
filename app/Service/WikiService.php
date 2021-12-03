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

use App\Constants\ErrorCode;
use App\Constants\ProjectConstant;
use App\Constants\StatusConstant;
use App\Exception\BusinessException;
use App\Model\Project;
use App\Model\User;
use App\Model\Wiki;
use App\Service\Dao\ProjectDao;
use App\Service\Dao\WikiDao;
use App\Service\Formatter\UserFormatter;
use App\Service\Formatter\WikiFormatter;
use Han\Utils\Service;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Codec\Json;

class WikiService extends Service
{
    #[Inject]
    protected WikiDao $dao;

    #[Inject]
    protected WikiFormatter $formatter;

    public function createDoc(array $input, User $user, Project $project)
    {
        $parentId = (int) ($input['parent'] ?? null);
        $projectKey = $input['project_key'] ?? '';
        $name = $input['name'] ?? '';
        $contents = $input['contents'] ?? '';

        $parent = $this->dao->first($parentId, true);

        if (empty($name)) {
            throw new BusinessException(ErrorCode::WIKI_NAME_NOT_EMPTY);
        }

        if ($this->dao->existsNameInSameParent($projectKey, $parent->id, $name)) {
            throw new BusinessException(ErrorCode::WIKI_NAME_NOT_REPEAT);
        }

        $model = new Wiki();
        $model->project_key = $project->key;
        $model->d = ProjectConstant::WIKI_CONTENTS;
        $model->del_flag = StatusConstant::NOT_DELETED;
        $model->name = $name;
        $model->pt = $this->getPathTree($parent);
        $model->version = 1;
        $model->creator = di()->get(UserFormatter::class)->base($user);
        $model->editor = [];
        $model->contents = $contents;

        //         TODO 需要需改
        //        $isSendMsg = $input['isSendMsg'] && true;
        //        Event::fire(new WikiEvent($projectKey, $insValues['creator'], ['event_key' => 'create_wiki', 'isSendMsg' => $isSendMsg, 'data' => ['wiki_id' => $id->__toString()]]));
        return $this->show($input, $id, $user);
    }

    public function getPathTree(?Wiki $parent): array
    {
        if ($parent === null) {
            return [0];
        }

        return array_merge($parent->pt, [$parent->id]);
    }

    public function isPermissionAllowed($projectKey, $permission, User $user)
    {
        $uid = $user->id;

        $project = di(ProjectDao::class)->firstByKey($projectKey);
        $isAllowed = di(AclService::class)->isAllowed($uid, $permission, $project);
        if (! $isAllowed && in_array($permission, ['view_project', 'manage_project'])) {
            if ($user->email === 'admin@action.view') {
                return true;
            }
            $project = di(ProjectDao::class)->firstByKey($projectKey);
            if ($project && isset($project->principal, $project->principal['id']) && $uid === $project->principal['id']) {
                return true;
            }
        }
        return $isAllowed;
    }

    public function show($input, int $id, User $user)
    {
        $document = $this->dao->first($id, true)->toArray();

        if ($this->dao->existsWidUser($id, $user->id)) {
            $document['favorited'] = true;
        }
        $newest = [];
        $newest['name'] = $document['name'];
        $newest['editor'] = isset($document['editor']) ? JSON::decode($document['editor']) : JSON::decode($document['creator']);
        $newest['updated_at'] = isset($document['updated_at']) ? $document['updated_at'] : $document['created_at'];
        $newest['version'] = $document['version'];

        //         TODO 先注释后续再看
        //        $v = $input['v'] ?? '';
        //
        //        if (isset($v) && intval($v) != $document['version']) {
        //            // 传过来的版本如果和数据表存储的不相等
        //            $wiki = $this->dao->firstVersion($input['project_key'], $id, $v);
        //            if ($wiki) {
        //                throw new \UnexpectedValueException('the version does not exist.', -11957);
        //            }
        //            $document['name'] = $wiki['name'];
        //            $document['contents'] = $wiki['contents'];
        //            $document['editor'] = $wiki['editor'];
        //            $document['updated_at'] = $wiki['updated_at'];
        //            $document['version'] = $wiki['version'];
        //        }

        $document['versions'] = $this->dao->search($input, $id);

        array_unshift($newest, $document['versions']);

        $path = $this->getPathTreeDetail($input['project_key'], Json::decode($document['pt']));
        return [$this->arrange($document), $path];
    }

    public function getPathTreeDetail($projectKey, $pt)
    {
        $parents = [];
        $ps = $this->dao->getName($projectKey, $pt);
        foreach ($ps as $val) {
            $parents[$val['_id']->__toString()] = $val['name'];
        }

        $path = [];
        foreach ($pt as $pid) {
            if ($pid === '0') {
                $path[] = ['id' => '0', 'name' => 'root'];
            } elseif (isset($parents[$pid])) {
                $path[] = ['id' => $pid, 'name' => $parents[$pid]];
            }
        }
        return $path;
    }

    public function arrange($data)
    {
        if (! is_array($data)) {
            return $data;
        }

        foreach ($data as $k => $val) {
            $data[$k] = $this->arrange($val);
        }

        return $data;
    }

    public function createFolder(array $input, User $user, Project $project)
    {
        $parentId = $input['parent'] ?? null;
        if (! isset($parentId)) {
            throw new BusinessException(ErrorCode::PARENT_NOT_EMPTY);
        }
        $name = $input['name'] ?? '';
        if (! $name) {
            throw new BusinessException(ErrorCode::WIKI_NAME_NOT_EMPTY);
        }

        $parentId = (int) $parentId;
        $parent = null;
        if ($parentId > 0) {
            $parent = $this->dao->first($parentId, true);
        }

        $isExists = $this->dao->existsNameInSameParent($project->key, $parent->id, $name);
        if ($isExists) {
            throw new BusinessException(ErrorCode::WIKI_NAME_NOT_REPEAT);
        }

        $model = new Wiki();
        $model->project_key = $project->key;
        $model->parent = $parentId;
        $model->name = $name;
        $model->pt = $this->getPathTree($parent);
        $model->d = ProjectConstant::WIKI_FOLDER;
        $model->creator = di()->get(UserFormatter::class)->small($user);
        $model->editor = [];
        $model->del_flag = StatusConstant::NOT_DELETED;
        $model->save();

        return $this->formatter->base($model);
    }
}
