<?php

declare(strict_types=1);

namespace App\Console\Webhooks\GitHub;

use App\Repo;

class Create
{
    /**
     * @param $json_content
     *
     * @throws \Exception
     */
    public static function handle($json_content): void
    {
        [
            'installation_id' => $installation_id,
            'rid' => $rid,
            'repo_full_name' => $repo_full_name,
            'ref_type' => $ref_type,
        ] = \KhsCI\Support\Webhooks\GitHub\Create::handle($json_content);

        Repo::updateGitHubInstallationIdByRid('github', (int) $rid, $repo_full_name, (int) $installation_id);

        switch ($ref_type) {
            case 'branch':
                $branch = $ref_type;
        }
    }
}
