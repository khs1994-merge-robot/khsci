<?php

declare(strict_types=1);

namespace KhsCI\Providers;

use KhsCI\Service\Users\GitHubClient;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class UserProvider implements ServiceProviderInterface
{
    public function register(Container $pimple): void
    {
        $pimple['user_basic_info'] = function ($app) {
            return new GitHubClient($app['curl'], $app['config']['api_url']);
        };
    }
}
