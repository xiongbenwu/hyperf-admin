<?php


namespace App\Logic\Admin;


use App\Constants\Constants;
use App\Exception\EmptyException;
use App\Exception\LoginException;
use App\Exception\StatusException;
use App\Model\SystemUserModel;
use App\Service\AuthService;
use App\Util\AccessToken;
use App\Util\Payload;
use App\Util\Prefix;
use App\Util\Redis;

class LoginLogic
{
    public function login(string $username, string $password)
    {

        $user = SystemUserModel::query()->where('username', $username)->first();

        if (empty($user)) {
            throw new \Exception('账号不存在！', 1);
        }
        if (0 === intval($user->status)) {
            throw new \Exception('账号已被禁用，请联系管理员！', 1);
        }

        $max_count = 5;//可重试次数

        $redis = Redis::getInstance();

        $key = Prefix::getLoginErrCount($username);

        $login_err_count = $redis->get($key);
        if (false === $login_err_count) {
            $login_err_count = 0;
            $redis->set($key, $login_err_count, 3600);
        }
        if ($login_err_count >= $max_count) {
            throw new \Exception('尝试次数达到上限，锁定一小时内禁止登录！', 1);
        }
        //判断连续输错次数  可重试5次
        if (!password_verify($password, $user->password)) {
            //错误次数+1
            $redis->incr($key);
            $login_err_count++;
            $diff = $max_count - $login_err_count;

            if ($diff) {
                throw new \Exception("账号或密码错误，还有{$diff}次尝试机会！", 1);
            } else {
                throw new \Exception('尝试次数达到上限，锁定一小时内禁止登录！', 1);
            }
        }
        //清除错误次数
        $redis->del($key);

        //查询角色名称
        $authService = new AuthService();
        $auth = $authService->info($user->role_id);
        if (!$auth) {
            throw new EmptyException('当前用户角色不存在，请联系管理员！');
        }
        if (1 != $auth->status) {
            throw new StatusException('当前用户角色被禁用，请联系管理员！');
        }

        $accessToken = new AccessToken();

        $app_name = config('app_name', '');

        $app_key = config('app_key', '');

        if (empty($app_key) || empty($app_name)) {
            throw new LoginException('配置有误！', 1);
        }

        $cur_time = time();

        $payload = new Payload();

        $payload['jti'] = uuid(16);
        $payload['iss'] = $app_name;
        $payload['sub'] = 'api.onetech.site';
        $payload['aud'] = 'api.onetech.site';
        $payload['ita'] = $cur_time;
        $payload['nbf'] = $cur_time;
        $payload['exp'] = $cur_time + 3600 * 24 * 10;
        $payload['scopes'] = Constants::SCOPE_ROLE;
        $payload['data'] = [
            'user_id' => $user->id,
            'user_name' => $user->username,
            'role_id' => $user->role_id,
            'role_name' => $auth->title
        ];
        $token = $accessToken->createToken($payload);

        $payload['exp'] = $cur_time + 84300;
        $payload['scopes'] = Constants::SCOPE_REFRESH;

        $refresh_token = $accessToken->createToken($payload);

        return compact('token', 'refresh_token');
    }

    public function refreshToken($refresh): array
    {

        $accessToken = new AccessToken();

        $jwt = $accessToken->checkRefreshToken($refresh);

        $data = (array)($jwt['data']);


        $accessToken = new AccessToken();

        $app_name = config('app_name', '');

        $app_key = config('app_key', '');

        if (empty($app_key) || empty($app_name)) {
            throw new LoginException('配置有误！', 1);
        }

        $cur_time = time();

        $payload = new Payload();

        $payload['jti'] = uuid(16);
        $payload['iss'] = $app_name;
        $payload['sub'] = 'api.onetech.site';
        $payload['aud'] = 'api.onetech.site';
        $payload['ita'] = $cur_time;
        $payload['nbf'] = $cur_time;
        $payload['exp'] = $cur_time + 3600;
        $payload['scopes'] = Constants::SCOPE_ROLE;
        $payload['data'] = $data;

        $token = $accessToken->createToken($payload);

        $payload['exp'] = $cur_time + 84300;
        $payload['scopes'] = Constants::SCOPE_REFRESH;

        $refresh_token = $accessToken->createToken($payload);

        return compact('token', 'refresh_token');

    }
}