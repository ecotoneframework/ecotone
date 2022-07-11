<?php

namespace Ecotone\Dbal\ObjectManager;

use Doctrine\Persistence\ManagerRegistry;
use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Throwable;

class ObjectManagerInterceptor
{
    /**
     * @var string[]
     */
    private $connectionReferenceNames;

    public function __construct(array $connectionReferenceNames)
    {
        $this->connectionReferenceNames = $connectionReferenceNames;
    }

    public function transactional(MethodInvocation $methodInvocation, ReferenceSearchService $referenceSearchService)
    {
        /** @var ManagerRegistry[] $objectManagers */
        $objectManagers = [];

        foreach ($this->connectionReferenceNames as $connectionReferenceName) {
            $dbalConnectionFactory = $referenceSearchService->get($connectionReferenceName);
            if ($dbalConnectionFactory instanceof ManagerRegistryConnectionFactory) {
                $objectManagers[] =  DbalReconnectableConnectionFactory::getManagerRegistryAndConnectionName($dbalConnectionFactory)[0];
            }
        }

        try {
            $result = $methodInvocation->proceed();

            foreach ($objectManagers as $objectManager) {
                foreach ($objectManager->getManagers() as $manager) {
                    $manager->flush();
                    $manager->clear();
                }
            }
        } catch (Throwable $exception) {
            foreach ($objectManagers as $objectManager) {
                foreach ($objectManager->getManagers() as $manager) {
                    $manager->clear();
                }
            }

            throw $exception;
        }


        return $result;
    }
}
