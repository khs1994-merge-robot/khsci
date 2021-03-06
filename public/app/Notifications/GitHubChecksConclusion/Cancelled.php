<?php

declare(strict_types=1);

namespace App\Notifications\GitHubChecksConclusion;

use App\Build;
use App\Notifications\GitHubAppChecks;
use Exception;
use KhsCI\Support\CI;

class Cancelled extends Passed
{
    /**
     * @throws Exception
     */
    public function handle(): void
    {
        if ('github' !== $this->git_type) {
            return;
        }

        GitHubAppChecks::send(
            $this->job_key_id,
            null,
            CI::GITHUB_CHECK_SUITE_STATUS_COMPLETED,
            (int) Build::getStartAt($this->job_key_id),
            (int) Build::getStopAt($this->job_key_id),
            CI::GITHUB_CHECK_SUITE_CONCLUSION_CANCELLED,
            null,
            null,
            $this->markdown(),
            null,
            null
        );
    }

    /**
     * @return string
     */
    public function cancelled()
    {
        return self::$header.<<<EOF

# Build Configuration

|Build Option      | Setting    |
| --               |   --       |  
| Language         | $this->language  |
| Operating System | $this->os        |

<details>
<summary><strong>Build Configuration</strong></summary>

```json
$this->config
```

</details>

# Build Log

```bash
$this->build_log
```

EOF;
    }
}
