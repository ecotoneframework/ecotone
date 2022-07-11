<?php

namespace Ecotone\SymfonyBundle\DepedencyInjection\Compiler;

use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class CacheCleaner implements CacheClearerInterface
{
    public function clear($cacheDir)
    {
        MessagingSystemConfiguration::cleanCache($cacheDir . EcotoneCompilerPass::CACHE_DIRECTORY_SUFFIX);
    }
}
