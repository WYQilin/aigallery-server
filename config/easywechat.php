<?php

return [
    /*
     * 默认配置，将会合并到各模块中
     */
    'defaults' => [
        'http' => [
            'timeout' => 5.0,
        ],
    ],

    /*
     * 小程序
     */
     'mini_app' => [
         'default' => [
             'app_id'     => env('WECHAT_MINI_APP_APPID', ''),
             'secret'     => env('WECHAT_MINI_APP_SECRET', ''),
             'token'      => env('WECHAT_MINI_APP_TOKEN', ''),
             'aes_key'    => env('WECHAT_MINI_APP_AES_KEY', ''),
         ],
     ],

    /*
     * 企业微信
     */
    'work' => [
        'default' => [
            'corp_id'    => env('WECHAT_WORK_CORP_ID', ''),
            'secret'     => env('WECHAT_WORK_SECRET', ''),
            'token'      => env('WECHAT_WORK_TOKEN', ''),
            'aes_key'    => env('WECHAT_WORK_AES_KEY', ''),
        ],
    ],

    /*
     * 微信支付
     */
    // 'pay' => [
    //     'default' => [
    //         'app_id'             => env('WECHAT_PAY_APPID', ''),
    //         'mch_id'             => env('WECHAT_PAY_MCH_ID', 'your-mch-id'),
    //         'private_key'        => '/data/private/certs/apiclient_key.pem',
    //         'certificate'        => '/data/private/certs/apiclient_cert.pem',
    //         'notify_url'         => 'http://example.com/payments/wechat-notify',                           // 默认支付结果通知地址
    //          /**
    //           * 证书序列号，可通过命令从证书获取：
    //           * `openssl x509 -in application_cert.pem -noout -serial`
    //           */
    //          'certificate_serial_no' => '6F2BADBE1738B07EE45C6A85C5F86EE343CAABC3',
    //
    //          'http' => [
    //              'base_uri' => 'https://api.mch.weixin.qq.com/',
    //          ],
    //
    //          // v2 API 秘钥
    //          //'v2_secret_key' => '26db3e15cfedb44abfbb5fe94fxxxxx',
    //
    //          // v3 API 秘钥
    //          //'secret_key' => '43A03299A3C3FED3D8CE7B820Fxxxxx',
    //
    //          // 注意 此处为微信支付平台证书 https://pay.weixin.qq.com/wiki/doc/apiv3/apis/wechatpay5_1.shtml
    //          'platform_certs' => [
    //              '/data/private/certs/platform_key.pem',
    //          ],
    //     ],
    // ],
];
