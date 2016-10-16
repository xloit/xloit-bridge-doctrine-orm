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

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use ReflectionClass;

/**
 * An {@link EntityRepositoryTrait} trait.
 *
 * @package Xloit\Bridge\Doctrine\ORM
 */
trait EntityRepositoryTrait
{
    /**
     * Holds the max Results value.
     *
     * @var int
     */
    protected $maxResults = 50;

    /**
     *
     *
     * @var string
     */
    private $entityAlias;

    /**
     *
     *
     * @return string
     */
    abstract public function getClassName();

    /**
     *
     *
     * @return EntityManager
     */
    abstract protected function getEntityManager();

    /**
     *
     *
     * @return ClassMetadata
     */
    abstract protected function getClassMetadata();

    /**
     * Returns the MaxResults value.
     *
     * @return int
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * Sets the MaxResults value.
     *
     * @param int $maxResults
     *
     * @return static
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * Get related entity alias used in query. Will be used entity class name without namespace.
     *
     * @return string
     */
    public function getEntityAlias()
    {
        if (!$this->entityAlias) {
            $className       = $this->getClassName();
            $reflectionCLass = new ReflectionClass($className);

            if (is_string($reflectionCLass->getConstant('ALIAS_NAME'))) {
                $entityAlias = $reflectionCLass->getConstant('ALIAS_NAME');
            } else {
                $entityAlias = end(explode('\\', $className));
            }

            $this->entityAlias = preg_replace('/[^\W]/', '', $entityAlias);
        }

        return $this->entityAlias;
    }

    /**
     * Creates a new QueryBuilder instance that is pre populated for this entity name.
     *
     * @return EntityQueryBuilder
     */
    abstract public function createNewQueryBuilderInstance();

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return EntityQueryBuilder
     */
    public function createQueryBuilder($alias = null, $indexBy = null)
    {
        return $this->createNewQueryBuilderInstance()->selectFromRepositoryEntity($alias, $indexBy);
    }

    /**
     * Creates a new result set mapping builder for this entity. The column naming strategy is "INCREMENT".
     *
     * @param string $alias
     *
     * @return ResultSetMappingBuilder
     */
    public function createResultSetMappingBuilder($alias = null)
    {
        if (!$alias) {
            $alias = $this->getEntityAlias();
        }

        $resultSet = new ResultSetMappingBuilder(
            $this->getEntityManager(), ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT
        );
        $resultSet->addRootEntityFromClassMetadata($this->getClassName(), $alias);

        return $resultSet;
    }

    /**
     * Finds an entity by its primary key/identifier.
     *
     * @param mixed    $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     *
     * @return \Xloit\Std\Interop\Object\EntityInterface
     */
    abstract public function find($id, $lockMode = null, $lockVersion = null);

    /**
     * Finds entity ids.
     *
     * @return array
     */
    public function findAllIdentifiers()
    {
        $metadata    = $this->getClassMetadata();
        $identifiers = $metadata->getIdentifier();
        $qb          = $this->createQueryBuilder();

        foreach ($identifiers as $field) {
            $qb->select($qb->alias($field));
        }

        return $qb->fetchAllScalar();
    }

    /**
     * Retrieve the minimum value of a given column..
     *
     * @param string $column
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    public function min($column)
    {
        $qb = $this->createQueryBuilder();

        $qb->select(sprintf('MIN(%s) AS min_%s', $qb->alias($column), $column));

        return $qb->fetchSingleScalar();
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param string $column
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    public function max($column)
    {
        $qb = $this->createQueryBuilder();

        $qb->select(sprintf('MAX(%s) AS max_%s', $qb->alias($column), $column));

        return $qb->fetchSingleScalar();
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param string $column
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    public function sum($column)
    {
        $qb = $this->createQueryBuilder();

        $qb->select(sprintf('SUM(%s) AS sum_%s', $qb->alias($column), $column));

        return $qb->fetchSingleScalar();
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param string $column
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    public function avg($column)
    {
        $qb = $this->createQueryBuilder();

        $qb->select(sprintf('AVG(%s) AS avg_%s', $qb->alias($column), $column));

        return $qb->fetchSingleScalar();
    }

    /**
     * Retrieve paginator.
     *
     * @param int $page
     * @param int $perPage
     *
     * @return Tools\Pagination\Paginator
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    public function paginate($page = 1, $perPage = null)
    {
        $perPage = $perPage ?: $this->getMaxResults();
        $qb      = $this->createQueryBuilder();

        $qb->paginate($page, $perPage);

        return $this->getPaginator($qb);
    }

    /**
     * Indicates whether the given argument is an entity.
     *
     * @param mixed $entity
     *
     * @return bool
     */
    public function isEntity($entity)
    {
        return is_object($entity)
               && !$this->getEntityManager()->getMetadataFactory()->isTransient(ClassUtils::getClass($entity));
    }

    /**
     * Get the object identifier, single or composite.
     *
     * @param mixed $entity
     * @param bool  $single
     *
     * @return array|string
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getIdentifier($entity, $single = false)
    {
        $this->validateEntity($entity, __METHOD__);

        $entityClass   = ClassUtils::getClass($entity);
        $entityManager = $this->getEntityManager();
        $metadata      = $entityManager->getClassMetadata($entityClass);

        $metadata->validateIdentifier();

        $id = $metadata->getIdentifierValues($entity);

        /** @noinspection IsEmptyFunctionUsageInspection */
        if (empty($id)) {
            return $single ? reset($id) : $id;
        }

        foreach ($id as $field => &$idValue) {
            if (is_object($idValue)
                && $entityManager->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($idValue))
            ) {
                $singleId = $entityManager->getUnitOfWork()->getSingleIdentifierValue($idValue);

                if ($singleId === null) {
                    throw ORMInvalidArgumentException::invalidIdentifierBindingEntity();
                }

                $idValue = $singleId;
            }

            if (!in_array($field, $metadata->identifier, true)) {
                throw ORMException::missingIdentifierField($metadata->name, $field);
            }
        }

        unset($idValue);

        if (count($metadata->identifier) !== count($id)) {
            throw ORMException::unrecognizedIdentifierFields(
                $metadata->name, array_diff($metadata->identifier, $id)
            );
        }

        return $single ? reset($id) : $id;
    }

    /**
     * Tells the EntityManager to make an instance managed and persistent.
     *
     * @param mixed $entity
     * @param bool  $flush
     *
     * @return mixed
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertEntity($entity, $flush = true)
    {
        $this->validateEntity($entity, __METHOD__);

        return $this->persist($entity, $flush);
    }

    /**
     * Tells the EntityManager to make an instance managed and persistent.
     *
     * @param mixed $entity
     * @param bool  $flush
     *
     * @return mixed
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateEntity($entity, $flush = true)
    {
        $this->validateEntity($entity, __METHOD__);

        return $this->persist($entity, $flush);
    }

    /**
     * Tells the EntityManager to make an instance managed and persistent.
     *
     * @param mixed $entity
     * @param bool  $flush
     *
     * @return mixed
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeEntity($entity, $flush = true)
    {
        $entityManager = $this->getEntityManager();

        if (is_scalar($entity)) {
            $entity = $this->find($entity);
        }

        $this->validateEntity($entity, __METHOD__);
        $entityManager->remove($entity);

        if ($flush) {
            $entityManager->flush();
        }

        return $entity;
    }

    /**
     * Tells the EntityManager to make an instance managed and persistent.
     *
     * @param mixed $entity
     * @param bool  $flush
     *
     * @return mixed
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function persist($entity, $flush = true)
    {
        $entityManager = $this->getEntityManager();

        $this->validateEntity($entity, __METHOD__);
        $entityManager->persist($entity);

        if ($flush) {
            $entityManager->flush();
        }

        return $entity;
    }

    /**
     *
     *
     * @param array $data
     * @param mixed $entity
     *
     * @return mixed
     */
    public function toEntity(array $data, $entity = null)
    {
        $classMetadata = $this->getClassMetadata();

        if (!$this->isEntity($entity)) {
            $entity = $classMetadata->newInstance();
        }

        foreach ($data as $key => $value) {
            $classMetadata->setFieldValue($entity, $key, $value);
        }

        return $entity;
    }

    /**
     * Indicates whether the given entity is valid.
     *
     * @param mixed  $entity
     * @param string $method
     *
     * @return void
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    protected function validateEntity($entity, $method)
    {
        if (!$this->isEntity($entity)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    '%s expects parameter 1 to be a valid entity instance, %s provided instead', $method,
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }
    }

    /**
     *
     *
     * @param array $data
     *
     * @return mixed
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \InvalidArgumentException
     */
    public function create(array $data = [])
    {
        $entityManager    = $this->getEntityManager();
        $metadata         = $this->getClassMetadata();
        $entity           = $metadata->newInstance();
        $associationNames = $metadata->getAssociationNames();

        foreach ($associationNames as $association) {
            if ($metadata->isAssociationWithSingleJoinColumn($association)) {
                $associationMappings = $metadata->getAssociationMapping($association);
                $joinColumn          = $associationMappings['joinColumns'][0];

                if (!$joinColumn['nullable']) {
                    $targetEntityRepository = $entityManager->getRepository(
                        $metadata->getAssociationTargetClass($association)
                    );
                    $reflectionProperty     = $metadata->getReflectionProperty($association);
                    $targetIdentifier       = $reflectionProperty->getValue($entity);

                    if (null !== $targetIdentifier) {
                        $targetEntity = $targetEntityRepository->find($targetIdentifier);

                        if (null !== $targetEntity) {
                            $reflectionProperty->setValue($entity, $targetEntity);
                        }
                    } /** @noinspection UnSafeIsSetOverArrayInspection */
                    elseif (isset($data[$association]) && is_scalar($data[$association])) {
                        $targetIdentifier = $data[$association];
                        $targetEntity = $targetEntityRepository->find($targetIdentifier);

                        if (null !== $targetEntity) {
                            $data[$association] = $targetEntity;
                        }
                    }
                }
            }
        }

        if (count($data) > 0) {
            foreach ($data as $field => $value) {
                $metadata->setFieldValue($entity, $field, $value);
            }
        }

        return $entity;
    }

    /**
     * Returns the object and its properties as an array.
     *
     * @param mixed $entity
     *
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function toArray($entity)
    {
        $entityManager = $this->getEntityManager();
        $unitOfWork    = $entityManager->getUnitOfWork();
        $classMetadata = $this->getClassMetadata();
        $result        = [];

        foreach ($unitOfWork->getOriginalEntityData($entity) as $field => $value) {
            if ($classMetadata->hasAssociation($field)) {
                $associationMapping = $classMetadata->getAssociationMapping($field);

                // Only owning side of x-1 associations can have a FK column.
                if (!$associationMapping['isOwningSide'] || !($associationMapping['type'] & ClassMetadata::TO_ONE)) {
                    continue;
                }

                if ($value !== null) {
                    $newValId    = $unitOfWork->getEntityIdentifier($value);
                    $targetClass = $entityManager->getClassMetadata($associationMapping['targetEntity']);

                    foreach ($associationMapping['joinColumns'] as $joinColumn) {
                        $sourceColumn = $joinColumn['name'];
                        $targetColumn = $joinColumn['referencedColumnName'];

                        if ($value === null) {
                            $result[$sourceColumn] = null;
                        } elseif ($targetClass->containsForeignIdentifier) {
                            $result[$sourceColumn] = $newValId[$targetClass->getFieldForColumn($targetColumn)];
                        } else {
                            $result[$sourceColumn] = $newValId[$targetClass->fieldNames[$targetColumn]];
                        }
                    }
                }
            } elseif ($classMetadata->hasField($field)) {
                $columnName          = $classMetadata->getFieldName($field);
                $result[$columnName] = $value;
            }
        }

        return $result;
    }
}
