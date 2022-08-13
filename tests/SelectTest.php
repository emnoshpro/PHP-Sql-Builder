<?php
//
// +-----------------------------------------------------------+
// | testSelect.php                                            |
// +-----------------------------------------------------------+
// | Put your description here                                 |
// +-----------------------------------------------------------+
// | Copyright (©) 2022                                        |
// +-----------------------------------------------------------+
// | Authors: Mehernosh Mohta <emnosh.pro@gmail.com.au>        |
// +-----------------------------------------------------------+
//
    declare(strict_types=1);

    use PHPUnit\Framework\TestCase;
    use Sql;

    define('CLASS_PATH', dirname(BASE_PATH));

    final class SelectTest extends TestCase
    {
        public function testSelectAll(): void
        {
            $sql = Sql\Builder::select('m_user');
            $actual = $sql->__toString();
            $expected = 'SELECT * FROM m_user';
            $this->assertEquals($actual, $expected);
        }

        public function testSelectAllAlias(): void
        {
            $sql = Sql\Builder::select('m_user', 'u');

            $this->assertEquals("SELECT * FROM m_user AS u", $sql->__toString());
        }

        public function testSelectAllWhere(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->where(['a', 'like', 'teeee']);

            $this->assertEquals("SELECT * FROM m_user AS u WHERE a like 'teeee'", $sql->__toString());
        }

        public function testSelectSomeFromTable(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->columns('a,b');

            $this->assertEquals("SELECT a, b FROM m_user AS u", $sql->__toString());
        }

        public function testSelectSomeFromTableWhere(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->columns('a,b')
                    ->where(['a', 'like', 'teeee']);

            $this->assertEquals("SELECT a, b FROM m_user AS u WHERE a like 'teeee'", $sql->__toString());
        }

        public function testSelectSomeFromTableUnwantedWhereString(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->columns('a,b')
                    ->where('a', 'like', 'teeee', Sql\Builder::OR);

            $this->assertEquals("SELECT a, b FROM m_user AS u WHERE a like 'teeee'", $sql->__toString());
        }

        public function testSelectSomeFromTableUnwantedWhereTwoString(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->columns('a,b')
                    ->where('a', 'like', 'teeee', Sql\Builder::OR)
                    ->where(['b', 'like', 'geeee'], Sql\Builder::AND);

            $this->assertEquals("SELECT a, b FROM m_user AS u WHERE a like 'teeee' OR b like 'geeee'", $sql->__toString());
        }

        public function testSelectSomeFromTableWhereString(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->columns('a,b')
                    ->where('a', 'like', 'teeee');

            $this->assertEquals("SELECT a, b FROM m_user AS u WHERE a like 'teeee'", $sql->__toString());
        }

        public function testSelectSomeFromTableWhereStringWithOperator(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->columns('a,b')
                    ->where('a', 'like', 'teeee', Sql\Builder::OR)
                    ->where(['bee', '=', 1]);

            $this->assertEquals("SELECT a, b FROM m_user AS u WHERE a like 'teeee' OR bee = 1", $sql->__toString());
        }

        public function testSelectSomeFromTableMultiWhere(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->columns('a,b')
                    ->where(['em', 'like', 'hundreds'])
                    ->where(['bee', '=', 1]);

            $this->assertEquals("SELECT a, b FROM m_user AS u WHERE em like 'hundreds' AND bee = 1", $sql->__toString());
        }

        public function testSelectSomeFromTableWhereNotArray(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->columns('a,b')
                    ->where('zee', '=', 100);

            $this->assertEquals("SELECT a, b FROM m_user AS u WHERE zee = 100", $sql->__toString());
        }

        public function testSelectSomeFromTableMultiWhereWithOperator(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->columns('a,b')
                    ->where(['em', 'like', 'hundreds'], Sql\Builder::OR)
                    ->where(['kat', '!=', 'hu'])
                    ->where(['humi', '!=', 'zara']);

            $this->assertEquals("SELECT a, b FROM m_user AS u WHERE em like 'hundreds' OR kat != 'hu' AND humi != 'zara'", $sql->__toString());
        }

        public function testSelectSomeFromTableMultiWhereWithTwoOperator(): void
        {
            $sql = Sql\Builder::select('m_user', 'u')
                    ->columns('a,b')
                    ->where(['em', 'like', 'hundreds'], Sql\Builder::OR)
                    ->where(['kat', '!=', 'hu'], Sql\Builder::OR)
                    ->where(['humi', '!=', 'zara']);

            $this->assertEquals("SELECT a, b FROM m_user AS u WHERE em like 'hundreds' OR kat != 'hu' OR humi != 'zara'", $sql->__toString());
        }
    }

