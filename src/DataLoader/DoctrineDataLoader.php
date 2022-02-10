<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\TableBundle\Extension\PaginationExtension;

class DoctrineDataLoader extends AbstractDataLoader
{
    public const OPTION_QUERY_BUILDER = 'query_builder';

    public const OPTION_DEFAULT_LIMIT = 'default_limit';

    public function __construct(
        protected PaginationExtension $paginationExtension
    ) {
    }

    public function getResults(): iterable
    {
        $this->paginationExtension->setLimit($this->options[self::OPTION_DEFAULT_LIMIT]);

        if ($this->paginationExtension->getLimit()) {
            $this->options[self::OPTION_QUERY_BUILDER]
                ->setMaxResults($this->paginationExtension->getLimit())
                ->setFirstResult(($this->paginationExtension->getCurrentPage() - 1) * $this->paginationExtension->getLimit());
        }

        $paginator = new Paginator(
            $this->options[self::OPTION_QUERY_BUILDER]
        );

        $this->paginationExtension->setTotalResults(\count($paginator));

        return $paginator->getIterator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault(self::OPTION_DEFAULT_LIMIT, 25);
        $resolver->setRequired(self::OPTION_QUERY_BUILDER);
        $resolver->setAllowedTypes(self::OPTION_QUERY_BUILDER, 'Doctrine\ORM\QueryBuilder');
    }
}