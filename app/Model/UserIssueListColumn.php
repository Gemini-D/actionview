<?php

declare (strict_types=1);
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
 * @property string $project_key 项目key
 * @property array $user 
 * @property array $column_keys 
 * @property array $columns 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class UserIssueListColumn extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'user_issue_list_columns';
    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'project_key', 'user', 'column_keys', 'columns', 'created_at', 'updated_at'];
    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'int', 'user' => 'json', 'columns' => 'json', 'column_keys' => 'json', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}