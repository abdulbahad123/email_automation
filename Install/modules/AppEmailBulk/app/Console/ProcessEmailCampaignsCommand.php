<?php

namespace Modules\AppEmailBulk\Console;

use Illuminate\Console\Command;
use Modules\AppEmailBulk\Services\EmailCampaignProcessor;

class ProcessEmailCampaignsCommand extends Command
{
    protected $signature = 'email:process-campaigns {--campaign= : Optional campaign id_secure} {--limit=10 : Max campaigns to process per run}';
    protected $description = 'Process running bulk email campaigns in scalable batches';

    public function handle(): int
    {
        $campaignId = $this->option('campaign') ?: null;
        $limit = max(1, (int) $this->option('limit'));

        $result = EmailCampaignProcessor::processDueCampaigns($limit, $campaignId);

        $this->info(sprintf(
            'Checked: %d | Sent: %d | Queued: %d | Failed: %d',
            $result['campaigns_checked'],
            $result['sent'],
            $result['queued'],
            $result['failed']
        ));

        foreach ($result['messages'] as $msg) {
            $this->line(' - ' . $msg);
        }

        return self::SUCCESS;
    }
}

