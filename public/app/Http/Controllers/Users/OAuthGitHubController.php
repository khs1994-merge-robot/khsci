<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use Exception;
use KhsCI\Service\OAuth\CodingClient;
use KhsCI\Service\OAuth\GiteeClient;
use KhsCI\Service\OAuth\GitHubClient;
use KhsCI\Support\Response;
use KhsCI\Support\Session;

class OAuthGitHubController
{
    /**
     * @var GitHubClient|GitHubClient|CodingClient|GiteeClient
     */
    protected static $oauth;

    protected static $git_type = 'github';

    use OAuthTrait;

    public function getLoginUrl(): void
    {
        $git_type = static::$git_type;

        /*
         * logout -> unset access_token
         *
         * OAuth login -> get access_token and expire from Session | expire one day
         */
        if (Session::get($git_type.'.access_token') and Session::get($git_type.'.expire') > time()) {
            $username_from_session = Session::get($git_type.'.username');

            Response::redirect(implode('/', ['/profile', $git_type, $username_from_session]));
        }

        $state = session_create_id();

        Session::put($git_type.'.state', $state);

        $url = static::$oauth->getLoginUrl($state);

        Response::redirect($url);
    }

    /**
     * @throws Exception
     */
    public function getAccessToken(): void
    {
        $state = Session::pull(static::$git_type.'.state');

        $this->getAccessTokenCommon($state);
    }
}
