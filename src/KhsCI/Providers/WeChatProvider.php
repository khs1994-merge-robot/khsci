<?php

declare(strict_types=1);

namespace KhsCI\Providers;

use KhsCI\Service\WeChat\Template\WeChatClient;
use KhsCI\Support\Cache;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use WeChat\WeChat;

class WeChatProvider implements ServiceProviderInterface
{
    public function register(Container $pimple): void
    {
        $pimple['wechat'] = function ($app) {
            return new WeChat(
                $app['config']['wechat']['app_id'],
                $app['config']['wechat']['app_secret'],
                $app['config']['wechat']['token'],
                Cache::store(),
                $app['config']['tencent_ai']['app_id'],
                $app['config']['tencent_ai']['app_key']
            );
        };

        $pimple['wechat_template_message'] = function ($app) {
            return new WeChatClient($app);
        };
    }
}
