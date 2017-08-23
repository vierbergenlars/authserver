<?php
/**
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) $today.date  Lars Vierbergen
 *
 * his program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace App\Tests\Plugin;

use App\Plugin\TokenParser;

class TokenParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParseClassWithNamespace()
    {
        $tokenParser = new TokenParser(<<<'EOL'
<?php

/**
* Blabla
*/

namespace Test\Namespace\Parts;

class TestClass {
    private $t1;
    public $t2;

    public function fn1() {
        return $this->fn2();
    }

    private function fn2() {
        return this->t1;
    }
}
EOL
        );

        $classes = $tokenParser->getDefinedClasses();

        $this->assertEquals(['Test\\Namespace\\Parts\\TestClass'], $classes);
    }

    public function testParseClassWithoutNamespace()
    {
        $tokenParser = new TokenParser(<<<'EOL'
<?php


/**
* Blabla
*/
class TestClass {
    private $t1;
    public $t2;

    public function fn1() {
        return $this->fn2();
    }

    private function fn2() {
        return this->t1;
    }
}
EOL
        );

        $classes = $tokenParser->getDefinedClasses();

        $this->assertEquals(['\\TestClass'], $classes);
    }

    public function testParseMultiClassWithNamespace()
    {
        $tokenParser = new TokenParser(<<<'EOL'
<?php

namespace Test\Namespace\Parts;
/**
* Blabla
*/
class TestClass {
    private $t1;
    public $t2;

    public function fn1() {
        return $this->fn2();
    }

    private function fn2() {
        return this->t1;
    }
}
class TestClass2 implements \Serializable {
    private $t1;
    public $t2;

    public function fn1() {
        return $this->fn2();
    }

    private function fn2() {
        return this->t1;
    }
}
EOL
        );

        $classes = $tokenParser->getDefinedClasses();

        $this->assertEquals(['Test\\Namespace\\Parts\\TestClass', 'Test\\Namespace\\Parts\\TestClass2'], $classes);
    }
}
