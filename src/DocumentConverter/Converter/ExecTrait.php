<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter\Converter;

trait ExecTrait
{
    protected function exec($cmd, &$out, &$return, $timeout = 60)
    {
        $cmd = new \Tine20\ProcWrap\Cmd($cmd);
        $cmd->setTimeoutInSeconds($timeout);

        pcntl_async_signals(true);
        $cmd->exec();
        $return = $cmd->getExitCode();
        $stdErr = $cmd->getStdErr();
        $out = ($stdErr ? $stdErr . PHP_EOL : '') . $cmd->getStdOut();
        pcntl_async_signals(false);
    }
}
