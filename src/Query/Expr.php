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

namespace Xloit\Bridge\Doctrine\ORM\Query;

use Doctrine\ORM\Query\Expr as DoctrineExpr;

/**
 * An {@link Expr} class.
 *
 * @package Xloit\Bridge\Doctrine\ORM\Query
 */
class Expr extends DoctrineExpr
{
    /**
     * Creates an instance of range, with the given argument.
     *
     * @param mixed $val Valued to be inspected by range values.
     * @param int   $x   Starting range value to be used.
     * @param int   $y   End point value to be used.
     *
     * @return DoctrineExpr\Andx
     */
    public function range($val, $x, $y)
    {
        return $this->andX($this->gt($val, $x), $this->lt($val, $y));
    }

    /**
     * Creates an instance of NOT BETWEEN() function, with the given argument.
     *
     * @param mixed $val Valued to be inspected by range values.
     * @param int   $x   Starting range value to be used in BETWEEN() function.
     * @param int   $y   End point value to be used in BETWEEN() function.
     *
     * @return string A NOT BETWEEN expression.
     */
    public function notBetween($val, $x, $y)
    {
        return $val . ' NOT BETWEEN ' . $x . ' AND ' . $y;
    }
}
