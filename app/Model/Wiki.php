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
namespace App\Model;

/**
 * @property int $id
 * @property int $wid
 * @property string $project_key 项目KEY
 * @property string $d
 * @property string $del_flag
 * @property string $name
 * @property array $pt
 * @property string $user
 * @property int $parent
 * @property string $contents
 * @property int $version
 * @property array $creator
 * @property array $editor
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Wiki extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wiki';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'wid', 'project_key', 'd', 'del_flag', 'name', 'pt', 'user', 'parent', 'contents', 'version', 'creator', 'editor', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'int', 'wid' => 'integer', 'pt' => 'json', 'creator' => 'json', 'editor' => 'json', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'parent' => 'integer', 'version' => 'integer'];
}
