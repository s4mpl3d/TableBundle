<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
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

namespace whatwedo\TableBundle\Table;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use whatwedo\CoreBundle\Formatter\DefaultFormatter;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class Column extends AbstractColumn implements SortableColumnInterface
{

    /**
     * @var string $tableIdentifier
     */
    protected $tableIdentifier;

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => $this->acronym,
            'callable' => null,
            'accessor_path' => $this->acronym,
            'formatter' => DefaultFormatter::class,
            'sortable' => true,
        ]);

        $resolver->setDefault('sort_expression', function (Options $options) {
            return $options['accessor_path'];
        });
    }

    /**
     * gets the content of the row
     *
     * @param $row
     * @return string
     */
    public function getContents($row)
    {
        if (is_callable($this->options['callable'])) {
            if (is_array($this->options['callable'])) {
                return call_user_func($this->options['callable'], [$row]);
            }

            return $this->options['callable']($row);
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        try {
            return $propertyAccessor->getValue($row, $this->options['accessor_path']);
        } catch (UnexpectedTypeException $e) {
            return '';
        } catch (NoSuchPropertyException $e) {
            return $e->getMessage();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render($row)
    {
        $data = $this->getContents($row);

        $formatter = $this->options['formatter'];

        if (is_string($formatter)) {
            $formatterObj = $this->formatterManager->getFormatter($formatter);
            return $formatterObj->getHtml($data);
        }

        if (is_callable($formatter)) {
            return $formatter($data);
        }

        return $data;
    }

    public function getOrderValue($row)
    {
        $data = $this->getContents($row);
        $formatter = $this->options['formatter'];

        if (is_string($formatter)) {
            $formatterObj = $this->formatterManager->getFormatter($formatter);
            return $formatterObj->getOrderValue($data);
        }

        if (is_callable($formatter)) {
            return $formatter($data);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->options['label'];
    }

    /**
     * @return string
     */
    public function getSortExpression()
    {
        return $this->options['sort_expression'];
    }

    /**
     * @return bool
     */
    public function isSortable()
    {
        return $this->options['sortable'];
    }

    /**
     * @param boolean $sortable
     * @return $this
     */
    public function setSortable($sortable)
    {
        $this->options['sortable'] = $sortable;
        return $this;
    }

    /**
     * @param ParameterBag $query
     * @return string
     */
    public function getOrderQueryASC(ParameterBag $query)
    {
        return $this->getOrderQuery($query, '1', '1');
    }

    /**
     * @param ParameterBag $query
     * @return string
     */
    public function getOrderQueryDESC(ParameterBag $query)
    {
        return $this->getOrderQuery($query, '1', '0');
    }

    /**
     * @param ParameterBag $query
     * @return string
     */
    public function getDeleteOrder(ParameterBag $query)
    {
        return $this->getOrderQuery($query, '0', '1');
    }

    /**
     * @param ParameterBag $query
     * @param $order
     * @return bool
     */
    public function isOrdered(ParameterBag $query, $order)
    {
        return $query->has($this->getOrderEnabledQueryParameter())
            && $query->get($this->getOrderEnabledQueryParameter()) == '1'
            && $query->has($this->getOrderAscQueryParameter())
            && $query->get($this->getOrderAscQueryParameter()) == ($order == 'ASC') ? '1' : '0';
    }

    /**
     * @param ParameterBag $query
     * @param $enabled
     * @param $asc
     * @return string
     */
    private function getOrderQuery(ParameterBag $query, $enabled, $asc)
    {
        $queryData = array_replace($query->all(), [
            $this->getOrderEnabledQueryParameter() => $enabled,
            $this->getOrderAscQueryParameter() => $asc
        ]);
        // remove parameter where is_order_... equals '0' aka not active
        $removeLater = [];
        foreach (array_keys($queryData) as $key) {
            if (substr($key, 0, strlen(SortableColumnInterface::ORDER_ENABLED)) == SortableColumnInterface::ORDER_ENABLED) {
                if ($queryData[$key] == '0') {
                    $suffix = substr($key, strlen(SortableColumnInterface::ORDER_ENABLED));
                    $removeLater[] = SortableColumnInterface::ORDER_ASC.$suffix;
                    $removeLater[] = $key;
                }
            }
        }
        foreach ($removeLater as $key) {
            if (array_key_exists($key, $queryData)) {
                unset($queryData[$key]);
            }
        }
        return count($queryData) > 0 ? '?'.http_build_query($queryData) : '?';
    }

    /**
     * @param string $identifier
     */
    public function setTableIdentifier($identifier)
    {
        $this->tableIdentifier = $identifier;
    }

    /**
     * @return string
     */
    public function getOrderEnabledQueryParameter()
    {
        return static::ORDER_ENABLED.$this->tableIdentifier.'_'.str_replace('.', '_', $this->getAcronym());
    }

    /**
     * @return string
     */
    public function getOrderAscQueryParameter()
    {
        return static::ORDER_ASC.$this->tableIdentifier.'_'.str_replace('.', '_', $this->getAcronym());
    }

}
