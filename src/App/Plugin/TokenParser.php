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

namespace App\Plugin;


/**
 * Locates all classes declared in PHP code.
 *
 * @package App\Plugin
 */
class TokenParser
{
    private $tokens;

    private $ptr;

    private $numTokens;

    public function __construct($code)
    {
        $this->tokens = token_get_all($code);
        $this->numTokens = count($this->tokens);
        $this->ptr = -1;
    }

    /**
     * Gets token at the current position
     * @return array|string
     */
    private function getToken()
    {
        return $this->tokens[$this->ptr];
    }

    /**
     * Advances position pointer and gets token at next position.
     *
     * Skips insignificant tokens, like whitespace and comments.
     * @return array|null|string Returns null if at EOF, else current token.
     */
    private function next()
    {
        while(++$this->ptr < $this->numTokens) {
            $token = $this->getToken();
            switch($token[0]) {
                case T_WHITESPACE:
                case T_COMMENT:
                case T_DOC_COMMENT:
                    continue 2;
                default:
                    return $token;
            }
        }
        return null;
    }

    /**
     * Advances position pointer until a token matching a certain type is encountered.
     * @param string[]|int[] $tokenTypes Array of single characters or token types to stop on.
     * @return array|null|string Returns null if at EOF, or current token.
     */
    private function nextUntil($tokenTypes) {
        do {
            $token = $this->next();
        } while($token !== null && !in_array($token[0], $tokenTypes, true));
        return $token;
    }

    /**
     * Asserts that the current token is of a certain type.
     * @param int $token The asserted token type
     * @throws \LogicException When the asserted type does not match the reality.
     */
    private function assertToken($token) {
        $currentToken = $this->getToken();
        if(!is_array($currentToken))
            throw new \LogicException('Expected '.token_name($token).' but got '.$currentToken);
        if($currentToken[0] !== $token)
            throw new \LogicException('Expected '.token_name($token).' but got '.token_name($currentToken[0]).': '.$currentToken[1]);
    }

    /**
     * Reconstructs a namespace when starting from {@link T_NAMESPACE}
     * @return string The full namespace that was parsed
     */
    private function parseNamespace()
    {
        $this->assertToken(T_NAMESPACE);
        $ns = '';
        $token = $this->nextUntil([T_STRING, T_NS_SEPARATOR]);
        do {
            $ns.=$token[1];
        } while(($token = $this->next()) && $token !== ';');
        return $ns;
    }

    /**
     * Constructs a class when starting from {@link T_CLASS}
     * @return null|string The class name that was parsed, or null if at EOF
     */
    private function parseClass()
    {
        $this->assertToken(T_CLASS);
        $class = '';
        $token = $this->nextUntil([T_STRING, T_NS_SEPARATOR]);
        do {
            switch($token[0]) {
                case T_STRING:
                case T_NS_SEPARATOR:
                    $class.=$token[1];
                    break;
                default:
                    return $class;
            }
        } while($token = $this->next());

        return null;
    }

    /**
     * Gets a list of all fully qualified class names declared inside the given piece of code.
     * @return string[]
     */
    public function getDefinedClasses()
    {
        $currentNs = '';
        $classes = [];

        while($token = $this->nextUntil([T_NAMESPACE, T_CLASS])) {
            switch($token[0]) {
                case T_NAMESPACE:
                    $currentNs = $this->parseNamespace();
                    break;
                case T_CLASS:
                    $class = $this->parseClass();
                    if($class)
                        $classes[] = $currentNs.'\\'.$class;
                    break;
            }
        }

        return $classes;
    }

}
