<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\GetAccessToken;
use App\Http\Controllers\Users\JWTController;
use App\Repo;
use App\User;
use Exception;
use KhsCI\KhsCI;

class SyncController
{
    /**
     * @var KhsCI
     */
    private $khsci;

    private $git_type;

    private $uid;

    private $access_token;

    /**
     * @throws Exception
     */
    public function __invoke(): void
    {
        list($this->git_type, $this->uid) = JwtController::getUser();

        $this->access_token = GetAccessToken::getAccessTokenByUid((int) $this->uid, $this->git_type);

        $this->khsci = new KhsCI(
            [$this->git_type.'_access_token' => $this->access_token], $this->git_type
        );

        if ('github' === $this->git_type) {
            $this->getOrgs();

            return;
        }

        // sync user basic info

        $this->getUserBasicInfo();

        // sync user repos

        $this->getRepo();

        // sync orgs

        $this->getOrgs();
    }

    /**
     * sync user basic info.
     *
     * @throws Exception
     */
    private function getUserBasicInfo(): void
    {
        list('uid' => $uid,
            'name' => $name,
            'email' => $email,
            'pic' => $pic
            ) = $this->khsci->user_basic_info->getUserInfo();

        User::updateUserInfo($this->git_type, $this->uid, $name, $email, $pic, $this->access_token);
    }

    /**
     * Sync user repos.
     *
     * @throws Exception
     */
    private function getRepo(): void
    {
        $page = 0;

        do {
            ++$page;

            $json = $this->khsci->user_basic_info->getRepos($page, false);

            $num_pre_page = count(json_decode($json));

            $this->parseRepo($json);
        } while ($num_pre_page >= 30);
    }

    /**
     * Sync orgs.
     *
     * @throws Exception
     */
    private function getOrgs(): void
    {
        $orgs = $this->khsci->user_basic_info->listOrgs();

        if (!$orgs) {
            return;
        }

        foreach (json_decode($orgs, true) as $k) {
            $org_id = $k['id'];

            $org_name = $k['login'];

            $org_pic = $k['avatar_url'];

            User::updateUserInfo((int) $org_id, null, $org_name, null, $org_pic, true, $this->git_type);

            User::setOrgAdmin((int) $org_id, (int) $this->uid, $this->git_type);
        }

        // check org status

        $orgs = User::getOrgByAdmin($this->uid, $this->git_type);

        foreach ($orgs as $k) {
            $org_name = $k['username'];
            $output = $this->khsci->orgs->exists($org_name);

            if (!$output) {
                User::delete($this->git_type, $org_name);
            }

            if ('github' !== $this->git_type) {
                $this->getOrgsRepo($org_name);
            }
        }
    }

    /**
     * Sync orgs repos.
     *
     * @param string $org_name
     *
     * @throws Exception
     */
    private function getOrgsRepo(string $org_name): void
    {
        $page = 0;

        do {
            ++$page;

            $json = $this->khsci->orgs->listRepo($org_name, 1);

            $num_pre_page = count(json_decode($json));

            $this->parseRepo($json);
        } while ($num_pre_page >= 30);
    }

    /**
     * parse repo json output.
     *
     * @param string $json
     *
     * @throws Exception
     */
    private function parseRepo(string $json): void
    {
        if ($obj = json_decode($json)) {
            for ($i = 0; $i < 30; ++$i) {
                $obj_repo = $obj[$i] ?? false;

                if (false === $obj_repo) {
                    break;
                }

                $repo_full_name = $obj_repo->full_name;

                $default_branch = $obj_repo->default_branch;

                /**
                 * 获取 repo 全名，默认分支，是否为管理员（拥有全部权限）.
                 *
                 * gitee permission
                 *
                 * github *s
                 */
                $admin = $obj_repo->permissions->admin ?? $obj_repo->permission->admin ?? null;

                $insert_collaborators = null;
                $insert_admin = null;

                if ($admin) {
                    // uid 是管理员
                    $insert_admin = $this->uid;
                } else {
                    // uid 是协作者
                    $insert_collaborators = $this->uid;
                }

                $rid = $obj_repo->id;

                Repo::updateRepoInfo((int) $rid,
                    $repo_full_name,
                    $insert_admin,
                    $insert_collaborators,
                    $default_branch,
                    $this->git_type
                );
            }
        }
    }
}
