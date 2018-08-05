<?php

declare(strict_types=1);

namespace App\Http\Controllers\Builds;

use App\Http\Controllers\Users\JWTController;

class StarController
{
    /**
     * star a repository based on the currently logged in user.
     *
     * post
     *
     * /repo/{repository.id}/star
     *
     * @param array $args
     *
     * @throws \Exception
     */
    public function __invoke(...$args): void
    {
        list($username, $repo_name) = $args;

        JWTController::checkByRepo(...$args);
    }

    /**
     * unstar a repository based on the currently logged in user.
     *
     * post
     *
     * /repo/{repository.slug}/unstar
     *
     * @param array $args
     *
     * @throws \Exception
     */
    public function unStar(...$args): void
    {
        list($username, $repo_name) = $args;

        JWTController::checkByRepo(...$args);
    }
}
