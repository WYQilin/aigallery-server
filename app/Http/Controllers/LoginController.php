<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use EasyWeChat\MiniApp\Application;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $code = $request->input('code');
        $server = app('easywechat.mini_app');
        $session = $server->getUtils()->codeToSession($code);

        if (isset($session['openid'])) {
            $openid = $session['openid'];
            $sessionKey = $session['session_key'];

            // 根据 openid 创建或查找用户
            $user = User::firstOrCreate(['openid' => $openid], [
                'name' => $this->genUserNumber()
            ]);

            // 生成 JWT 或 Laravel 的 Sanctum 令牌
            $token = $user->createToken('wechat')->plainTextToken;

            return response()->json([
                'token' => $token,
                'id' => $user->id,
            ]);
        }

        return response()->json(['message' => '登录失败'], 401);
    }

    private function genUserNumber()
    {
        $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $username = "";
        for ($i = 0; $i < 6; $i++) {
            $username .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return strtoupper(base_convert(time() - 1420070400, 10, 36)) . $username;
    }
}
