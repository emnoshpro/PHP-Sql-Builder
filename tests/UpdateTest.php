<?php
//
// +-----------------------------------------------------------+
// | UpdateTest.php                            |
// +-----------------------------------------------------------+
// | Put your description here                               |
// +-----------------------------------------------------------+
// | Copyright (©) 2022                              |
// +-----------------------------------------------------------+
// | Authors: Mehernosh Mohta <emnosh.pro@gmail.com.au>      |
// +--------------------------------------------------------- +
//

    declare(strict_types=1);

    use PHPUnit\Framework\TestCase;
    use Sql;

    define('CLASS_PATH', dirname(BASE_PATH));

    final class UpdateTest extends TestCase
    {
        public function testSelectAll(): void
        {
            $sql = Sql\Builder::update('m_user', 'u')
                ->columns(['a' => 'hello ', 'b' => 'test ', 'c' => ' 123 '])
                ->where(['user_id', '=', 1]);
            $this->assertEquals("UPDATE m_user SET a = 'hello', b = 'test', c = 123 WHERE user_id = 1", $sql->__toString());
        }
    }
