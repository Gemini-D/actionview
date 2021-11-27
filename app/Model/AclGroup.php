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
 * @property string $name
 * @property array $users
 * @property string $principal
 * @property int $public_scope
 * @property string $description
 * @property string $directory
 * @property string $ldap_dn
 * @property string $sync_flag
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AclGroup extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'acl_group';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'users', 'principal', 'public_scope', 'description', 'directory', 'ldap_dn', 'sync_flag', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'int', 'users' => 'json', 'public_scope' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
