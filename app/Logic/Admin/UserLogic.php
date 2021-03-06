<?php


namespace App\Logic\Admin;


use App\Exception\UserNotFoundException;
use App\Model\SystemUserModel;
use App\Service\UserService;

class UserLogic
{
    /**
     *
     * @return array
     */
    public function getList(array $query)
    {
        $where = [
            [
                'username', '!=', 'admin'
            ]
        ];

        if (!empty($query['username'])) {
            $where[] = [
                'username', '=', $query['username']
            ];
        }

        if (!empty($query['mobile'])) {
            $where[] = [
                'mobile', '=', $query['mobile']
            ];
        }

        if (!empty($query['email'])) {
            $where[] = [
                'email', '=', $query['email']
            ];
        }

        if (!empty($query['role_id'])) {
            $where[] = [
                'role_id', '=', $query['role_id']
            ];
        }
        $page = $query['page'] ?: 1;
        $limit = $query['limit'] ?: 20;
        $count = 0;

        $di = di(UserService::class);

        $count = $di->count($where,'*');
        $list = [];

        if ($count){
            $list = $di->select($where, [
                'id', 'username', 'nickname', 'role_id', 'avatar', 'gender', 'mobile', 'email', 'remark', 'status', 'created_at'
            ], $page, $limit)->toArray();
        }

        return [
            'page' => $page,
            'limit' => $limit,
            'list' => $list,
            'count' => $count
        ];
    }

    /**
     *
     * @param int $user_id
     * @return array
     */
    public function getUser(int $user_id)
    {

        $di = di(UserService::class);

        $user = $di->getUser($user_id);

        if (empty($user)) {
            throw new UserNotFoundException();
        }

        return $user->toArray();
    }

    public function getUserByName(string $user_name)
    {
        return di(UserService::class)->getUserByName($user_name);
    }

    /**
     * @param int $user_id
     * @param int $role_id
     * @param string $nickname
     * @param int $gender
     * @param string $avatar
     * @param string $mobile
     * @param string $email
     * @param string $remark
     * @return int
     */
    public function save(int $user_id, int $role_id, string $nickname, int $gender, string $avatar, string $mobile, string $email, string $remark)
    {

        $di = di(UserService::class);

        $user = $di->getUser($user_id);

        if (empty($user)) {
            throw new UserNotFoundException();
        }

        return $di->save($user_id, [
            'role_id' => $role_id,
            'nickname' => $nickname,
            'gender' => $gender,
            'avatar' => $avatar,
            'mobile' => $mobile,
            'email' => $email,
            'remark' => $remark
        ]);

    }


    public function add(string $username, string $password, int $role_id, string $nickname, int $gender, string $avatar, string $mobile, string $email, string $remark)
    {
        $data = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role_id' => $role_id,
            'nickname' => $nickname,
            'gender' => $gender,
            'avatar' => $avatar,
            'mobile' => $mobile,
            'email' => $email,
            'remark' => $remark,
        ];

        return di(UserService::class)->add($data);
    }

}