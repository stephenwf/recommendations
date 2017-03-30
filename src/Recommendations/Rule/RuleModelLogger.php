<?php

namespace eLife\Recommendations\Rule;

use eLife\Recommendations\RuleModel;
use Psr\Log\LoggerInterface;

trait RuleModelLogger
{
    public function debug(RuleModel $ruleModel, $message, $context = [])
    {
        $this->log('debug', $ruleModel, $message, $context);
    }

    public function error(RuleModel $ruleModel, $message, $context = [])
    {
        $this->log('error', $ruleModel, $message, $context);
    }

    public function log($level, RuleModel $ruleModel, $message, $context = [])
    {
        $context['model'] = $ruleModel;
        if (!$this->logger instanceof LoggerInterface) {
            return;
        }
        $this->logger->log(
            $level,
            sprintf('%s %s<%s> %s', __CLASS__, $ruleModel->getType(), $ruleModel->getId(), $message),
            $context
        );
    }
}
