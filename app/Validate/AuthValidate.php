<?php

namespace App\Validate;

use think\Validate;

class AuthValidate extends Validate
{

    protected $rule = [
        'id' => 'require',
        'title' => 'require|min:5|max:30',
        'desc' => 'require|min:5|max:255',
    ];

    protected $message = [
        'id.require' => '参数id不能为空！',
        'title.require' => '权限名称不能为空！',
        'title.min' => '权限名称长度不能少于5位有效字符！',
        'title.max' => '权限名称长度不能大于30位有效字符！',
        'desc.require' => '权限描述不能为空！',
        'desc.min' => '权限描述长度不能少于5位有效字符！',
        'desc.max' => '权限描述长度不能大于255位有效字符！',
    ];

    protected $scene = [
        'add' => ['title', 'desc'],
        'edit' => ['id', 'title', 'desc'],
        'del' => ['id'],
        'forbid' => ['id'],
        'resume' => ['id']
    ];

}