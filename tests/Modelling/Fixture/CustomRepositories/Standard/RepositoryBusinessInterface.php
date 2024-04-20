<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard;

use Ecotone\Modelling\Attribute\Repository;

interface RepositoryBusinessInterface
{
    #[Repository]
    public function getArticle(string $id): Article;

    #[Repository]
    public function getPage(string $id): Page;

    #[Repository]
    public function getAuthor(string $id): Author;
}
