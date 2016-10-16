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

use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query as DoctrineQuery;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use ReflectionClass;

/**
 * An {@link EntityQueryBuilder} class
 *
 * @package Xloit\Bridge\Doctrine\ORM
 *
 * @method EntityQueryBuilder select(mixed $select = null)
 * @method EntityQueryBuilder addSelect(mixed $select = null)
 * @method EntityQueryBuilder distinct(bool $flag = true)
 * @method EntityQueryBuilder indexBy(string $alias, string $indexBy)
 * @method EntityQueryBuilder join($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
 * @method EntityQueryBuilder innerJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
 * @method EntityQueryBuilder leftJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
 * @method EntityQueryBuilder where(mixed $predicates)
 * @method EntityQueryBuilder andWhere($arguments)
 * @method EntityQueryBuilder orWhere($arguments)
 * @method EntityQueryBuilder having(mixed $having)
 * @method EntityQueryBuilder andHaving(mixed $having)
 * @method EntityQueryBuilder orHaving(mixed $having)
 * @method EntityQueryBuilder addCriteria(Criteria $criteria)
 */
class EntityQueryBuilder extends QueryBuilder
{
    /**
     *
     *
     * @var EntityRepositoryInterface
     */
    protected $repository;

    /**
     *
     *
     * @var Query\Expr
     */
    protected $expr;

    /**
     *
     *
     * @var string
     */
    private $entityAlias;

    /**
     *
     *
     * @var string[]
     */
    private $fieldNames;

    /**
     * Constructor to prevent {@link EntityQueryBuilder} from being loaded more than once.
     *
     * @param EntityManager             $em
     * @param EntityRepositoryInterface $repository
     */
    public function __construct(EntityManager $em, EntityRepositoryInterface $repository)
    {
        parent::__construct($em);

        $this->setRepository($repository);
    }

    /**
     * Configure query to work with repository entity
     *
     * @param string $alias
     * @param string $indexBy The index for the from
     *
     * @return static
     */
    public function selectFromRepositoryEntity($alias = null, $indexBy = null)
    {
        if ($alias) {
            $this->entityAlias = $alias;
        }

        if (!$alias) {
            $alias = $this->getEntityAlias();
        }

        $this->select($alias)->from($this->getEntityClassName(), $alias, $indexBy);

        return $this;
    }

    /**
     *
     *
     * @param EntityRepositoryInterface $repository
     *
     * @return static
     */
    public function setRepository(EntityRepositoryInterface $repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     *
     *
     * @return DoctrineEntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     *
     *
     * @return string[]
     */
    public function getFieldNames()
    {
        if (!$this->fieldNames) {
            $classMetadata    = $this->getEntityManager()->getClassMetadata($this->getEntityClassName());
            $this->fieldNames = array_merge($classMetadata->getFieldNames(), $classMetadata->getAssociationNames());
        }

        return $this->fieldNames;
    }

    /**
     * Get related entity class name
     *
     * @return string
     */
    protected function getEntityClassName()
    {
        return $this->getRepository()->getClassName();
    }

    /**
     * Get related entity alias used in query. Will be used entity class name without namespace
     *
     * @return string
     */
    public function getEntityAlias()
    {
        if (!$this->entityAlias) {
            $className       = $this->getEntityClassName();
            $reflectionCLass = new ReflectionClass($className);

            if (is_string($reflectionCLass->getConstant('ALIAS_NAME'))) {
                $this->entityAlias = $reflectionCLass->getConstant('ALIAS_NAME');
            } else {
                if (method_exists($this->getRepository(), 'getEntityAlias')) {
                    $this->entityAlias = $this->getRepository()->getEntityAlias();
                } else {
                    $this->entityAlias = end(explode('\\', $className));
                }
            }

            $this->entityAlias = str_replace('.', '', $this->entityAlias);
        }

        return $this->entityAlias;
    }

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     * This producer method is intended for convenient inline usage. Example:
     *
     * <code>
     *     $qb = $em->createQueryBuilder();
     *     $qb
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where($qb->expr()->eq('u.id', 1));
     * </code>
     *
     * For more complex expression construction, consider storing the expression
     * builder object in a local variable.
     *
     * @return Query\Expr
     */
    public function expr()
    {
        if (!($this->expr instanceof Query\Expr)) {
            $this->expr = new Query\Expr;
        }

        return $this->expr;
    }

    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain entity type.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->delete('User', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter('user_id', 1);
     * </code>
     *
     * @param string $delete The class/type whose instances are subject to the deletion.
     * @param string $alias  The class/type alias used in the constructed query.
     *
     * @return static This QueryBuilder instance.
     */
    public function delete($delete = null, $alias = null)
    {
        if ($delete === null) {
            $delete = $this->getEntityClassName();

            if ($alias === null) {
                $alias = $this->getEntityAlias();
            }
        }

        return parent::delete($delete, $alias);
    }

    /**
     * Turns the query being built into a bulk update query that ranges over
     * a certain entity type.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->update('User', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where('u.id = ?');
     * </code>
     *
     * @param string $update The class/type whose instances are subject to the update.
     * @param string $alias  The class/type alias used in the constructed query.
     *
     * @return static This QueryBuilder instance.
     */
    public function update($update = null, $alias = null)
    {
        if ($update === null) {
            $update = $this->getEntityClassName();

            if ($alias === null) {
                $alias = $this->getEntityAlias();
            }
        }

        return parent::update($update, $alias);
    }

    /**
     * Creates and adds a query root corresponding to the entity identified by the given alias,
     * forming a cartesian product with any existing query roots.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u');
     * </code>
     *
     * @param string $from    The class name.
     * @param string $alias   The alias of the class.
     * @param string $indexBy The index for the from.
     *
     * @return static This QueryBuilder instance.
     */
    public function from($from = null, $alias = null, $indexBy = null)
    {
        if ($from === null) {
            $from = $this->getEntityClassName();

            if ($alias === null) {
                $alias = $this->getEntityAlias();
            }
        }

        return parent::from($from, $alias, $indexBy = null);
    }

    /**
     * Sets a new value for a field in a bulk update query.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->update('User', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where('u.id = ?');
     * </code>
     *
     * @param string $key   The key/field to set.
     * @param string $value The value, expression, placeholder, etc.
     *
     * @return static This QueryBuilder instance.
     */
    public function set($key, $value)
    {
        return parent::set($this->alias($key), $value);
    }

    /**
     * Limit max number of results
     *
     * @param int $maxResults
     * @param int $offset
     *
     * @return static
     * @throws Exception\InvalidArgumentException
     */
    public function limit($maxResults, $offset = null)
    {
        if (!is_int($maxResults)) {
            throw new Exception\InvalidArgumentException('Incorrect argument $maxResults. Only number allowed.');
        }

        if ($maxResults < 1) {
            throw new Exception\InvalidArgumentException(
                sprintf('Incorrect maximum results: %d. Only positive number allowed.', $maxResults)
            );
        }

        $this->setMaxResults($maxResults);

        if ($offset !== null) {
            if (!is_int($offset)) {
                throw new Exception\InvalidArgumentException('Incorrect argument $offset. Only number allowed.');
            }

            if ($offset < 0) {
                throw new Exception\InvalidArgumentException(
                    sprintf('Incorrect offset: %d. Only positive number allowed.', $offset)
                );
            }

            $this->setFirstResult($offset);
        }

        return $this;
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->groupBy('u.id');
     * </code>
     *
     * @param string $groupBy The grouping expression.
     *
     * @return static
     */
    public function groupBy($groupBy)
    {
        /** @var array $groupFields */
        $groupFields = func_get_args();

        if (count($groupFields) > 0) {
            parent::groupBy($this->alias(array_shift($groupFields)));

            foreach ($groupFields as $group) {
                $this->addGroupBy($group);
            }
        }

        return $this;
    }

    /**
     * Adds a grouping expression to the query.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->groupBy('u.lastLogin')
     *         ->addGroupBy('u.createdAt');
     * </code>
     *
     * @param string $groupBy The grouping expression.
     *
     * @return static
     */
    public function addGroupBy($groupBy)
    {
        /** @var array $groupFields */
        $groupFields = func_get_args();

        foreach ($groupFields as $group) {
            parent::addGroupBy($this->alias($group));
        }

        return $this;
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return static
     */
    public function orderBy($sort, $order = null)
    {
        if (is_string($sort)) {
            $sort = $this->alias($sort);
        }

        return parent::orderBy($sort, $order);
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return static
     */
    public function addOrderBy($sort, $order = null)
    {
        if (is_string($sort)) {
            $sort = $this->alias($sort);
        }

        return parent::addOrderBy($sort, $order);
    }

    /**
     *
     *
     * @param string $column
     *
     * @return static
     */
    public function orderAsc($column)
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     *
     *
     * @param string $column
     *
     * @return static
     */
    public function orderDesc($column)
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     *
     *
     * @param string $column
     *
     * @return static
     */
    public function addOrderAscBy($column)
    {
        return $this->addOrderBy($column, 'ASC');
    }

    /**
     *
     *
     * @param string $column
     *
     * @return static
     */
    public function addOrderDescBy($column)
    {
        return $this->addOrderBy($column, 'DESC');
    }

    /**
     * Limit results with selected page
     *
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return static
     * @throws Exception\InvalidArgumentException
     */
    public function paginate($page = 1, $itemsPerPage = 10)
    {
        if (!is_int($page) || !is_int($itemsPerPage)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Incorrect argument %s. Only number allowed.', is_int($page) ? '$itemsPerPage' : '$page'
                )
            );
        }

        if ($page < 1) {
            throw new Exception\InvalidArgumentException(
                sprintf('Incorrect page number: %d. Only positive number allowed.', $page)
            );
        }

        if ($itemsPerPage < 1) {
            throw new Exception\InvalidArgumentException(
                sprintf('Incorrect items per page: %d. Only positive number allowed.', $page)
            );
        }

        return $this->limit($itemsPerPage, ($page - 1) * $itemsPerPage);
    }

    /**
     * Fetch first entity
     *
     * @param array $parameters
     * @param int   $hydrationMode
     *
     * @return mixed|null
     * @throws Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function fetchOne(array $parameters = [], $hydrationMode = null)
    {
        $this->appendParameters($parameters);
        $this->limit(1, 0);

        return $this->getQuery()->getOneOrNullResult($hydrationMode);
    }

    /**
     * Fetch first column of first result row
     *
     * @param array $parameters
     * @param int   $hydrationMode
     *
     * @return mixed
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function fetchSingle(array $parameters = [], $hydrationMode = null)
    {
        $this->appendParameters($parameters);
        $this->limit(1, 0);

        return $this->getQuery()->getSingleResult($hydrationMode);
    }

    /**
     * Fetch first column of first result row
     *
     * @param array $parameters
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Xloit\Bridge\Doctrine\ORM\Exception\InvalidArgumentException
     */
    public function fetchSingleScalar(array $parameters = [])
    {
        return $this->fetchSingle($parameters, DoctrineQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * Fetch all entities
     *
     * @param array $parameters
     * @param int   $hydrationMode
     *
     * @return array
     */
    public function fetchAll(array $parameters = [], $hydrationMode = DoctrineQuery::HYDRATE_OBJECT)
    {
        $this->appendParameters($parameters);

        return $this->getQuery()->getResult($hydrationMode);
    }

    /**
     * Fetch all entities
     *
     * @param array $parameters
     *
     * @return array
     */
    public function fetchAllArray(array $parameters = [])
    {
        return $this->fetchAll($parameters, DoctrineQuery::HYDRATE_ARRAY);
    }

    /**
     * Fetch all entities
     *
     * @param array $parameters
     *
     * @return array
     */
    public function fetchAllScalar(array $parameters = [])
    {
        return $this->fetchAll($parameters, DoctrineQuery::HYDRATE_SCALAR);
    }

    /**
     * Fetch all entities
     *
     * @param array $parameters
     *
     * @return array
     */
    public function fetchAllSimple(array $parameters = [])
    {
        return $this->fetchAll($parameters, DoctrineQuery::HYDRATE_SIMPLEOBJECT);
    }

    /**
     *
     *
     * @param array $parameters
     *
     * @return static
     */
    public function appendParameters($parameters)
    {
        if (is_array($parameters)) {
            foreach ($parameters as $key => $value) {
                $type = null;

                if (is_array($value)) {
                    if (count($value) === 2) {
                        $type = $value[1];
                    }

                    $value = array_shift($value);
                } elseif ($value instanceof Parameter) {
                    $key   = $value->getKey();
                    $type  = $value->getType();
                    $value = $value->getName();
                }

                $this->setParameter($key, $value, $type);
            }
        }

        return $this;
    }

    /**
     * Auto append parameter and replace them with the parameter name
     *
     * @param mixed  $value
     * @param string $type
     * @param string $columnName
     *
     * @return string The parameter name
     */
    public function param($value, $type = null, $columnName = 'p')
    {
        $parameterName = $this->findUnusedParameterName($columnName);

        if ($type) {
            $this->appendParameters(
                [
                    $parameterName => [
                        $value,
                        $type
                    ]
                ]
            );
        } else {
            $this->appendParameters([$parameterName => $value]);
        }

        return sprintf(':%s', $parameterName);
    }

    /**
     *
     *
     * @param string $column
     *
     * @return string
     */
    public function alias($column)
    {
        if (!is_string($column)
            || strpos('.', $column) !== false
            || !in_array($column, $this->getFieldNames(), true)
        ) {
            return $column;
        }

        return sprintf('%s.%s', $this->getEntityAlias(), $column);
    }

    /**
     *
     *
     * @param string $fieldName
     * @param mixed  $value
     * @param string $format
     * @param bool   $doNotCastDatetime
     *
     * @return string
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function typeCastField($fieldName, $value, $format = null, $doNotCastDatetime = false)
    {
        $className     = $this->getEntityClassName();
        $classMetadata = $this->getEntityManager()->getClassMetadata($className);

        if (!$classMetadata->hasField($fieldName)) {
            return $value;
        }

        $field = $classMetadata->getFieldMapping($fieldName);

        switch ($field['type']) {
            case 'string':
                settype($value, 'string');
                break;
            case 'integer':
            case 'smallint':
                #case 'bigint':  // Don't try to manipulate bigints?
                settype($value, 'integer');
                break;
            case 'boolean':
                settype($value, 'boolean');
                break;
            case 'decimal':
                settype($value, 'decimal');
                break;
            case 'date':
                if ($value && !$doNotCastDatetime) {
                    if (!$format) {
                        $format = 'Y-m-d';
                    }

                    $value = DateTime::createFromFormat($format, $value);
                }
                break;
            case 'time':
                if ($value && !$doNotCastDatetime) {
                    if (!$format) {
                        $format = 'H:i:s';
                    }

                    $value = DateTime::createFromFormat($format, $value);
                }
                break;
            case 'datetime':
                if ($value && !$doNotCastDatetime) {
                    if (!$format) {
                        $format = 'Y-m-d H:i:s';
                    }

                    $value = DateTime::createFromFormat($format, $value);
                }
                break;
            case 'float':
                settype($value, 'float');
                break;
            default:
                break;
        }

        return $value;
    }

    /**
     * Find unused parameter name
     *
     * @param string $columnName
     *
     * @return string
     */
    protected function findUnusedParameterName($columnName = 'p')
    {
        $parameters = $this->getParameters()->map(
            function($parameter) {
                /** @var Parameter $parameter */
                return $parameter->getName();
            }
        );
        $index      = 0;

        do {
            $parameterName = $columnName . $index;
            $index++;
        } while ($parameters->contains($parameterName));

        return $parameterName;
    }

    /**
     * Adds support for magic finders.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return static
     * @throws ORMException
     * @throws Exception\BadMethodCallException If the method called is an invalid find* method or no find* method
     *                                          at all and therefore an invalid method call.
     */
    public function __call($method, $arguments)
    {
        $commands = [
            'callFunctionalityFields' => [
                'prefix'  => true,
                'methods' => [
                    'where'          => true,
                    'filterBy'       => true,
                    'orderBy'        => false,
                    'orderAscBy'     => false,
                    'orderDescBy'    => false,
                    'addOrderBy'     => false,
                    'addOrderAscBy'  => false,
                    'addOrderDescBy' => false,
                    'groupBy'        => false,
                    'addGroupBy'     => false
                ]
            ],
            'callWhereClauseFields'   => [
                'prefix'  => false,
                'methods' => [
                    'GreaterThanEqual' => true,
                    'GreaterThan'      => true,
                    'LessThanEqual'    => true,
                    'LessThan'         => true,
                    'NotEqual'         => true,
                    'Equal'            => true,
                    'NotIn'            => true,
                    'In'               => true,
                    'NotLike'          => true,
                    'Like'             => true,
                    'IsNull'           => false,
                    'IsNotNull'        => false,
                    'IsEmpty'          => false,
                    'IsNotEmpty'       => false
                ]
            ]
        ];

        $condition    = null;
        $fieldName    = null;
        $methodName   = null;
        $functionName = null;

        foreach ($commands as $command => $commandOptions) {
            $parsed = $this->parseCallMethods(
                $method, $commandOptions['methods'], $arguments, $commandOptions['prefix']
            );

            $condition    = $parsed['condition'];
            $fieldName    = $parsed['fieldName'];
            $methodName   = $parsed['methodName'];
            $functionName = $command;

            if ($fieldName && $methodName) {
                break;
            }
        }

        if (!$fieldName || !$methodName) {
            throw new Exception\BadMethodCallException(sprintf('Undefined method "%s".', $method));
        }

        return $this->{$functionName}($fieldName, $methodName, $arguments, $condition);
    }

    /**
     * A parseCallMethods function.
     *
     * @param string $method
     * @param array  $lists
     * @param array  $arguments
     * @param bool   $prefix
     *
     * @return array
     * @throws ORMException
     */
    protected function parseCallMethods($method, array $lists, array $arguments, $prefix = true)
    {
        $condition  = null;
        $fieldName  = null;
        $methodName = null;

        foreach ($lists as $name => $requireArgs) {
            if (strpos($method, 'and') === 0) {
                $method    = lcfirst(substr($method, 3));
                $condition = 'and';
            } elseif (strpos($method, 'or') === 0) {
                $method    = lcfirst(substr($method, 2));
                $condition = 'or';
            }

            if (strlen($method) < strlen($name)) {
                continue;
            }

            $position = false;

            if ($prefix && strpos($method, $name) === 0) {
                $position = 0;
            } elseif (!$prefix) {
                $position = strpos($method, $name, count($name));
            }

            if ($position !== false) {
                /** @noinspection IsEmptyFunctionUsageInspection */
                if ($requireArgs && empty($arguments)) {
                    throw ORMException::findByRequiresParameter(sprintf('%s::%s', static::class, $method));
                }

                if ($prefix) {
                    $fieldName  = substr($method, strlen($name));
                    $methodName = $name;
                } elseif (!$prefix) {
                    $fieldName  = substr($method, 0, $position);
                    $methodName = $name;
                }
                break;
            }
        }

        if ($fieldName && $methodName) {
            $fieldName     = lcfirst(Inflector::classify($fieldName));
            $className     = $this->getEntityClassName();
            $classMetadata = $this->getEntityManager()->getClassMetadata($className);

            if (!$classMetadata->hasField($fieldName) && !$classMetadata->hasAssociation($fieldName)) {
                throw ORMException::invalidFindByCall($className, $fieldName, $method);
            }
        }

        return [
            'condition'  => $condition,
            'fieldName'  => $fieldName,
            'methodName' => $methodName
        ];
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * A callFunctionalityFields function.
     *
     * @param string $fieldName
     * @param string $methodName
     * @param array  $arguments
     * @param string $condition
     *
     * @return static
     */
    private function callFunctionalityFields($fieldName, $methodName, array $arguments = [], $condition = null)
    {
        switch ($methodName) {
            case 'where':
            case 'filterBy':
                if ($condition === 'or') {
                    $this->orWhere(sprintf('%s = %s', $this->alias($fieldName), $arguments[0]));
                } else {
                    $this->andWhere(sprintf('%s = %s', $this->alias($fieldName), $arguments[0]));
                }
                break;
            case 'orderBy':
            case 'orderAscBy':
            case 'orderDescBy':
                if ($methodName === 'orderAscBy') {
                    $arguments = ['ASC'];
                } elseif ($methodName === 'orderDescBy') {
                    $arguments = ['DESC'];
                }

                if (count($arguments) === 1) {
                    $this->orderBy($fieldName, $arguments[0]);
                } else {
                    $this->orderBy($fieldName);
                }
                break;
            case 'addOrderBy':
            case 'addOrderAscBy':
            case 'addOrderDescBy':
                if ($methodName === 'addOrderAscBy') {
                    $arguments = ['ASC'];
                } elseif ($methodName === 'addOrderDescBy') {
                    $arguments = ['DESC'];
                }

                if (count($arguments) === 1) {
                    $this->addOrderBy($fieldName, $arguments[0]);
                } else {
                    $this->addOrderBy($fieldName);
                }
                break;
            case 'groupBy':
                $this->groupBy($this->alias($fieldName));
                break;
            case 'addGroupBy':
                $this->addGroupBy($this->alias($fieldName));
                break;
            default:
                // Do Nothing
                break;
        }

        return $this;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * A callWhereClauseFields function.
     *
     * @param string $fieldName
     * @param string $methodName
     * @param array  $arguments
     * @param string $condition
     *
     * @return static
     */
    private function callWhereClauseFields($fieldName, $methodName, array $arguments = [], $condition = null)
    {
        $expr = null;

        switch ($methodName) {
            case 'GreaterThanEqual':
                $expr = $this->expr()->gte($this->alias($fieldName), $this->param($arguments[0]));
                break;
            case 'GreaterThan':
                $expr = $this->expr()->gt($this->alias($fieldName), $this->param($arguments[0]));
                break;
            case 'LessThanEqual':
                $expr = $this->expr()->lte($this->alias($fieldName), $this->param($arguments[0]));
                break;
            case 'LessThan':
                $expr = $this->expr()->lt($this->alias($fieldName), $this->param($arguments[0]));
                break;
            case 'NotEqual':
                $expr = $this->expr()->neq($this->alias($fieldName), $this->param($arguments[0]));
                break;
            case 'Equal':
                $expr = $this->expr()->eq($this->alias($fieldName), $this->param($arguments[0]));
                break;
            case 'NotIn':
                $expr = $this->expr()->notIn($this->alias($fieldName), $this->param($arguments[0]));
                break;
            case 'In':
                $expr = $this->expr()->in($this->alias($fieldName), $this->param($arguments[0]));
                break;
            case 'NotLike':
                $expr = $this->expr()->notLike($this->alias($fieldName), $this->param($arguments[0]));
                break;
            case 'Like':
                $expr = $this->expr()->like($this->alias($fieldName), $this->param($arguments[0]));
                break;
            case 'IsNull':
                $expr = $this->expr()->isNull($this->alias($fieldName));
                break;
            case 'IsNotNull':
                $expr = $this->expr()->isNotNull($this->alias($fieldName));
                break;
            case 'IsEmpty':
                $expr = $this->expr()->orX(
                    $this->expr()->eq($this->alias($fieldName), $this->param('')),
                    $this->expr()->isNull($this->alias($fieldName))
                );
                break;
            case 'IsNotEmpty':
                $expr = $this->expr()->andX(
                    $this->expr()->neq($this->alias($fieldName), $this->param('')),
                    $this->expr()->isNotNull($this->alias($fieldName))
                );
                break;
            default:
                // Do Nothing
                break;
        }

        if ($expr) {
            if ($condition === 'or') {
                $this->orWhere($expr);
            } else {
                $this->andWhere($expr);
            }
        }

        return $this;
    }
}
