<?php

declare(strict_types=1);

namespace App\Console\Webhooks\GitHub;

use App\Build;
use App\Console\Webhooks\Skip;
use App\Notifications\GitHubAppChecks;

class Check
{
    /**
     * completed.
     *
     * requested 用户推送分支
     *
     * rerequested 用户点击了重新运行按钮
     *
     * @param $json_content
     *
     * @throws \Exception
     */
    public static function suite($json_content): void
    {
        [
            'installation_id' => $installation_id,
            'rid' => $rid,
            'repo_full_name' => $repo_full_name,
            'branch' => $branch,
            'commit_id' => $commit_id,
            'action' => $action,
            'account' => $account,
        ] = \KhsCI\Support\Webhooks\GitHub\Check::suite($json_content);

        (new Subject())
            ->register(new UpdateUserInfo($account, (int) $installation_id, (int) $rid, $repo_full_name))
            ->handle();

        'rerequested' === $action && Build::updateBuildStatusByCommitId('pending', (int) $rid, $branch, $commit_id);
    }

    /**
     * created updated rerequested requested_action.
     *
     * @param $json_content
     *
     * @throws \Exception
     */
    public static function run($json_content): void
    {
        [
            'installation_id' => $installation_id,
            'rid' => $rid,
            'repo_full_name' => $repo_full_name,
            'action' => $action,
            'commit_id' => $commit_id,
            'external_id' => $external_id,
            'check_suite_id' => $check_suite_id,
            'check_run_id' => $check_run_id,
            'branch' => $branch,
            'account' => $account,
        ] = \KhsCI\Support\Webhooks\GitHub\Check::run($json_content);

        if ('rerequested' === $action) {
            // 用户点击了某一 run 的 re-run
            Build::updateBuildStatusByCommitId('pending', (int) $rid, $branch, $commit_id);
        } elseif ('requested_action' === $action) {
            // 用户点击了按钮，CI 推送修复补丁
        } else {
            return;
        }

        $config = Build::getConfig((int) $external_id);

        $config_array = json_decode($config, true);

        (new Subject())
            ->register(new UpdateUserInfo($account, (int) $installation_id, (int) $rid, $repo_full_name))
            ->register(new Skip(null, (int) $external_id, $branch, $config))
            ->handle();

        if ($config_array) {
            Build::updateBuildStatus((int) $external_id, 'pending');
        }

        GitHubAppChecks::send((int) $external_id);
    }
}
