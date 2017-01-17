<?php
/**
 * Queue command.
 *
 * For listening to SQS.
 */

namespace eLife\Recommendations\Command;

use Symfony\Component\Console\Command\Command;

class QueueCommand extends Command
{
    public function __construct($name = null)
    {
        parent::__construct($name);
    }
}
