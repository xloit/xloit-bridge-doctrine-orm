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

namespace Xloit\Bridge\Doctrine\ORM;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Persistence\ObjectRepository;

/**
 * An {@link EntityRepositoryInterface} interface.
 *
 * @package Xloit\Bridge\Doctrine\ORM
 */
interface EntityRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * Returns the MaxResults value.
     *
     * @return int
     */
    public function getMaxResults();

    /**
     * Sets the MaxResults value.
     *
     * @param int $maxResults
     *
     * @return $this
     */
    public function setMaxResults($maxResults);

    /**
     * Get related entity alias used in query. Will be used entity class name without namespace.
     *
     * @return string
     */
    public function getEntityAlias();

    /**
     * Creates a new QueryBuilder instance that is pre populated for this entity name.
     *
     * @return EntityQueryBuilder
     */
    public function createNewQueryBuilderInstance();

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return EntityQueryBuilder
     */
    public function createQueryBuilder($alias = null, $indexBy = null);

    /**
     * Creates a new result set mapping builder for this entity. The column naming strategy is "INCREMENT".
     *
     * @param string $alias
     *
     * @return \Doctrine\ORM\Query\ResultSetMappingBuilder
     */
    public function createResultSetMappingBuilder($alias = null);

    /**
     * Creates a new Query instance based on a predefined metadata named query.
     *
     * @param string $queryName
     *
     * @return \Doctrine\ORM\Query
     */
    public function createNamedQuery($queryName);

    /**
     * Creates a native SQL query.
     *
     * @param string $queryName
     *
     * @return \Doctrine\ORM\NativeQuery
     */
    public function createNativeNamedQuery($queryName);

    /**
     * Finds entity ids.
     *
     * @return array
     */
    public function findAllIdentifiers();

    /**
     * Retrieve paginator.
     *
     * @param int $page
     * @param int $perPage
     *
     * @return Tools\Pagination\Paginator
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    public function paginate($page = 1, $perPage = null);

    /**
     * Indicates whether the given argument is an entity.
     *
     * @param mixed $entity
     *
     * @return bool
     */
    public function isEntity($entity);

    /**
     * Tells the EntityManager to make an instance managed and persistent.
     *
     * @param mixed $entity
     * @param bool  $flush
     *
     * @return mixed
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    public function insertEntity($entity, $flush = true);

    /**
     * Tells the EntityManager to make an instance managed and persistent.
     *
     * @param mixed $entity
     * @param bool  $flush
     *
     * @return mixed
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    public function updateEntity($entity, $flush = true);

    /**
     * Tells the EntityManager to make an instance managed and persistent.
     *
     * @param mixed $entity
     * @param bool  $flush
     *
     * @return mixed
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    public function removeEntity($entity, $flush = true);

    /**
     *
     *
     * @param array $data
     * @param mixed $entity
     *
     * @return mixed
     */
    public function toEntity(array $data, $entity = null);

    /**
     *
     *
     * @param array $data
     *
     * @return mixed
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \InvalidArgumentException
     */
    public function create(array $data = []);

    /**
     * Returns the object and its properties as an array.
     *
     * @param mixed $entity
     *
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function toArray($entity);

    /**
     * Clears the repository, causing all managed entities to become detached.
     *
     * @return void
     */
    public function clear();
}
