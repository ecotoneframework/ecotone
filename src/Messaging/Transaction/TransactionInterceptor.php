<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Transaction;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class TransactionInterceptor
 * @package SimplyCodedSoftware\Messaging\Transaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor()
 */
class TransactionInterceptor
{
    /**
     * @Around()
     * @param MethodInvocation $methodInvocation
     * @param ReferenceSearchService $referenceSearchService
     * @param Transactional $transactional
     * @return mixed
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
     * @throws \Throwable
     */
    public function transactional(MethodInvocation $methodInvocation, ReferenceSearchService $referenceSearchService, Transactional $transactional)
    {
        /** @var TransactionFactory[] $factories */
        $factories = [];
        foreach ($transactional->getFactoryReferenceNameList() as $referenceName) {
            $factories[] = $referenceSearchService->get($referenceName);
        }

        /** @var Transaction[] $runningTransactions */
        $runningTransactions = [];
        foreach ($factories as $factory) {
            $runningTransactions[] = $factory->begin();
        }

        try {
            $result = $methodInvocation->proceed();
        }catch (\Throwable $throwable) {
            foreach ($runningTransactions as $runningTransaction) {
                $runningTransaction->rollback();
            }

            throw $throwable;
        }

        foreach ($runningTransactions as $runningTransaction) {
            $runningTransaction->commit();
        }

        return $result;
    }
}