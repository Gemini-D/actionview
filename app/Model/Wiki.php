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
 * @property string $project_key 项目KEY
 * @property int $d 
 * @property string $del_flag 
 * @property string $name 
 * @property array $pt 
 * @property int $parent 
 * @property string $contents 
 * @property int $version 
 * @property array $creator 
 * @property array $editor 
 * @property array $attachments 
 * @property array $checkin 
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
    protected ?string $table = 'wiki';
    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'project_key', 'd', 'del_flag', 'name', 'pt', 'parent', 'contents', 'version', 'creator', 'editor', 'attachments', 'checkin', 'created_at', 'updated_at'];
    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'int', 'wid' => 'integer', 'pt' => 'json', 'creator' => 'json', 'editor' => 'json', 'attachments' => 'json', 'checkin' => 'json', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'parent' => 'integer', 'version' => 'integer', 'd' => 'integer'];
}