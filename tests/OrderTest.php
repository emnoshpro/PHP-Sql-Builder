<?php
//
// +---------------------------------------------------------+
// | OrderTest.php                                           |
// +---------------------------------------------------------+
// | Put your description here                               |
// +---------------------------------------------------------+
// | Copyright (©) 2022                                      |
// +---------------------------------------------------------+
// | Authors: Mehernosh Mohta <emnosh.pro@gmail.com.au>      |
// +-------------------------------------------------------- +
//

    declare(strict_types=1);

    use PHPUnit\Framework\TestCase;
    use Sql;

    define('CLASS_PATH', dirname(BASE_PATH));

    final class OrderTest extends TestCase
    {
        public function testOrderStringArgument(): void
        {
            $sql = Sql\Builder::select('m_user')
                ->orderBy('a', 'DESC');

            $expected = $sql->__toString();
            $actual = 'SELECT * FROM m_user ORDER BY A DESC';
            $this->assertEquals($actual, $expected);
        }

        public function testOrderStringArgumentNoOrderBy(): void
        {
            $sql = Sql\Builder::select('m_user')
                ->orderBy('a');

            $expected = $sql->__toString();
            $actual = 'SELECT * FROM m_user ORDER BY A ASC';
            $this->assertEquals($actual, $expected);
        }

        public function testOrderStringCommaArgument(): void
        {
            $sql = Sql\Builder::select('m_user')
                ->orderBy('a,b', 'DESC');

            $expected = $sql->__toString();
            $actual = 'SELECT * FROM m_user ORDER BY A DESC, B DESC';
            $this->assertEquals($actual, $expected);
        }

        public function testOrderArraySingleArgument(): void
        {
            $sql = Sql\Builder::select('m_user')
                ->orderBy(['a'], 'DESC');

            $expected = $sql->__toString();
            $actual = 'SELECT * FROM m_user ORDER BY A DESC';
            $this->assertEquals($actual, $expected);
        }

        public function testOrderArrayMultiArgument(): void
        {
            $sql = Sql\Builder::select('m_user')
                ->orderBy(['a,b,c,d'], 'DESC');

            $expected = $sql->__toString();
            $actual = 'SELECT * FROM m_user ORDER BY A,B,C,D DESC';
            $this->assertEquals($actual, $expected);
        }

        public function testOrderArrayAssocArgument(): void
        {
            $sql = Sql\Builder::select('m_user')
                ->orderBy(['A'=>'DESC']);

            $expected = $sql->__toString();
            $actual = 'SELECT * FROM m_user ORDER BY A DESC';
            $this->assertEquals($actual, $expected);
        }

        public function testOrderArrayAssocMultiArgument(): void
        {
            $sql = Sql\Builder::select('m_user')
                ->orderBy(['A'=>'DESC', 'B'=>'ASC']);

            $expected = $sql->__toString();
            $actual = 'SELECT * FROM m_user ORDER BY A DESC, B ASC';
            $this->assertEquals($actual, $expected);
        }
    }
