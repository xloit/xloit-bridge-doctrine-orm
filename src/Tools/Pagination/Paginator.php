<?php
/**
 * This source file is part of Xloit project.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * <http://www.opensource.org/licenses/mit-license.php>
 * If you did not receive a copy of the license and are unable to obtain it through the world-wide-web,
 * please send an email to <license@xloit.com> so we can send you a copy immediately.
 *
 * @license   MIT
 * @link      http://xloit.com
 * @copyright Copyright (c) 2016, Xloit. All rights reserved.
 */

namespace Xloit\Bridge\Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

/**
 * A {@link Paginator} class.
 *
 * @package Xloit\Bridge\Doctrine\ORM\Tools\Pagination
 */
class Paginator extends DoctrinePaginator
{
    /**
     *
     *
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * Constructor to prevent {@link Paginator} from being loaded more than once.
     *
     * @param QueryBuilder $queryBuilder        A Doctrine ORM query builder.
     * @param bool         $fetchJoinCollection Whether the query joins a collection (true by default).
     */
    public function __construct(QueryBuilder $queryBuilder, $fetchJoinCollection = true)
    {
        $this->queryBuilder = $queryBuilder;

        parent::__construct($queryBuilder, $fetchJoinCollection);
    }

    /**
     *
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     *
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return $this
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }
}
