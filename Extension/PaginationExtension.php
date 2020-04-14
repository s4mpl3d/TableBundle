<?php
/*
 * Copyright (c) 2017, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\TableBundle\Extension;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PaginationExtension.
 */
class PaginationExtension extends AbstractExtension
{
    const QUERY_PARAMETER_PAGE = 'page';

    const QUERY_PARAMETER_LIMIT = 'limit';

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var int
     */
    protected $limit = 25;

    /**
     * @var int
     */
    protected $totalResults = 0;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getCurrentPage(): int
    {
        $page = $this->getRequest()->query->getInt($this->getActionQueryParameter(static::QUERY_PARAMETER_PAGE), 1);
        if ($page < 1) {
            $page = 1;
        }

        return $page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $defaultLimit): self
    {
        $this->limit = $this->getRequest()->query->getInt($this->getActionQueryParameter(static::QUERY_PARAMETER_LIMIT), $defaultLimit);

        return $this;
    }

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    public function setTotalResults(int $totalResults): self
    {
        $this->totalResults = $totalResults;

        return $this;
    }

    public function getTotalPages(): int
    {
        if (-1 === $this->limit) {
            return 1;
        }

        return ceil($this->getTotalResults() / $this->limit);
    }

    public function getOffsetResults(): int
    {
        if (-1 === $this->limit) {
            return 0;
        }

        return ($this->getCurrentPage() - 1) * $this->limit;
    }

    /**
     * @param array $enabledBundles
     */
    public static function isEnabled($enabledBundles): bool
    {
        return true;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request|null
     */
    protected function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }
}
