<?php declare(strict_types=1);

namespace DocumentService\Action;

use DocumentService\Lock;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Config\Config;
use Zend\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

class Info implements MiddlewareInterface
{
    /**
     * @var Config
     */
    protected $config;

    public function __construct(array $configArray)
    {
        $this->config = new Config($configArray);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        $information = ['timestamp' => time()];
        $information['locks'] = (new Lock(false, 4, 4))->currentLocks();
        $information['files'] = $this->tempDir($this->config->get('tempDir'));
        $information['disk_free_space'] = disk_free_space($this->config->get('tempDir'));
        $information['build'] = file_get_contents(getcwd() . '/buildnumber');

        return new JsonResponse($information);
    }

    protected function tempDir($dir)
    {
        $rtn = [];

        $files = scandir($dir);
        foreach ($files as $file) {
            $info = [];
            $info['name'] = $file;

            $file = $dir . '/' . $file;
            $info['fileatime'] = fileatime($file);
            $info['filesize'] = filesize($file);

            $rtn[] = $info;
        }

        return $rtn;
    }
}
