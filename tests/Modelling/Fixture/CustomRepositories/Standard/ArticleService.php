<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard;

use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * licence Apache-2.0
 */
final class ArticleService
{
    #[CommandHandler('get.article.via.service')]
    public function getArticleViaService(string $id, array $metadata, RepositoryBusinessInterface $repository): ?Article
    {
        return $repository->findArticle($id, $metadata);
    }
}
