<?php

namespace App\Search;

class SearchGrammar
{
    protected $string;
    protected $position;
    protected $value;
    protected $cache;
    protected $cut;
    protected $errors;
    protected $warnings;

    protected function parseQuery()
    {
        $_position = $this->position;

        if (isset($this->cache['Query'][$_position])) {
            $_success = $this->cache['Query'][$_position]['success'];
            $this->position = $this->cache['Query'][$_position]['position'];
            $this->value = $this->cache['Query'][$_position]['value'];

            return $_success;
        }

        $_value9 = array();

        $_value5 = array();
        $_cut6 = $this->cut;

        while (true) {
            $_position4 = $this->position;

            $this->cut = false;
            $_value3 = array();

            $_position1 = $this->position;
            $_cut2 = $this->cut;

            $this->cut = false;
            $_success = $this->parse_();

            if (!$_success && !$this->cut) {
                $_success = true;
                $this->position = $_position1;
                $this->value = null;
            }

            $this->cut = $_cut2;

            if ($_success) {
                $_value3[] = $this->value;

                $_success = $this->parseParameter();

                if ($_success) {
                    $r = $this->value;
                }
            }

            if ($_success) {
                $_value3[] = $this->value;

                $this->value = $_value3;
            }

            if ($_success) {
                $this->value = call_user_func(function () use (&$r) {
                    return $r;
                });
            }

            if (!$_success) {
                break;
            }

            $_value5[] = $this->value;
        }

        if (!$this->cut) {
            $_success = true;
            $this->position = $_position4;
            $this->value = $_value5;
        }

        $this->cut = $_cut6;

        if ($_success) {
            $r = $this->value;
        }

        if ($_success) {
            $_value9[] = $this->value;

            $_position7 = $this->position;
            $_cut8 = $this->cut;

            $this->cut = false;
            $_success = $this->parse_();

            if (!$_success && !$this->cut) {
                $_success = true;
                $this->position = $_position7;
                $this->value = null;
            }

            $this->cut = $_cut8;
        }

        if ($_success) {
            $_value9[] = $this->value;

            $this->value = $_value9;
        }

        if ($_success) {
            $this->value = call_user_func(function () use (&$r, &$r) {
                return $r;
            });
        }

        $this->cache['Query'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, 'Query');
        }

        return $_success;
    }

    protected function parseParameter()
    {
        $_position = $this->position;

        if (isset($this->cache['Parameter'][$_position])) {
            $_success = $this->cache['Parameter'][$_position]['success'];
            $this->position = $this->cache['Parameter'][$_position]['position'];
            $this->value = $this->cache['Parameter'][$_position]['value'];

            return $_success;
        }

        $_value16 = array();

        $_success = $this->parseIdentifier();

        if ($_success) {
            $name = $this->value;
        }

        if ($_success) {
            $_value16[] = $this->value;

            $_position10 = $this->position;
            $_cut11 = $this->cut;

            $this->cut = false;
            $_success = $this->parse_();

            if (!$_success && !$this->cut) {
                $_success = true;
                $this->position = $_position10;
                $this->value = null;
            }

            $this->cut = $_cut11;
        }

        if ($_success) {
            $_value16[] = $this->value;

            $_position12 = $this->position;
            $_cut13 = $this->cut;

            $this->cut = false;
            if (substr($this->string, $this->position, strlen(":")) === ":") {
                $_success = true;
                $this->value = substr($this->string, $this->position, strlen(":"));
                $this->position += strlen(":");
            } else {
                $_success = false;

                $this->report($this->position, '":"');
            }

            if (!$_success && !$this->cut) {
                $this->position = $_position12;

                if (substr($this->string, $this->position, strlen("~")) === "~") {
                    $_success = true;
                    $this->value = substr($this->string, $this->position, strlen("~"));
                    $this->position += strlen("~");
                } else {
                    $_success = false;

                    $this->report($this->position, '"~"');
                }
            }

            $this->cut = $_cut13;

            if ($_success) {
                $t = $this->value;
            }
        }

        if ($_success) {
            $_value16[] = $this->value;

            $_position14 = $this->position;
            $_cut15 = $this->cut;

            $this->cut = false;
            $_success = $this->parse_();

            if (!$_success && !$this->cut) {
                $_success = true;
                $this->position = $_position14;
                $this->value = null;
            }

            $this->cut = $_cut15;
        }

        if ($_success) {
            $_value16[] = $this->value;

            $_success = $this->parseStr();

            if ($_success) {
                $value = $this->value;
            }
        }

        if ($_success) {
            $_value16[] = $this->value;

            $this->value = $_value16;
        }

        if ($_success) {
            $this->value = call_user_func(function () use (&$name, &$t, &$value) {
                return array('type' => $t, 'name'=>$name, 'value'=>$value);
            });
        }

        $this->cache['Parameter'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, 'Parameter');
        }

        return $_success;
    }

    protected function parseIdentifier()
    {
        $_position = $this->position;

        if (isset($this->cache['Identifier'][$_position])) {
            $_success = $this->cache['Identifier'][$_position]['success'];
            $this->position = $this->cache['Identifier'][$_position]['position'];
            $this->value = $this->cache['Identifier'][$_position]['value'];

            return $_success;
        }

        if (preg_match('/^[a-zA-Z0-9]$/', substr($this->string, $this->position, 1))) {
            $_success = true;
            $this->value = substr($this->string, $this->position, 1);
            $this->position += 1;
        } else {
            $_success = false;
        }

        if ($_success) {
            $_value18 = array($this->value);
            $_cut19 = $this->cut;

            while (true) {
                $_position17 = $this->position;

                $this->cut = false;
                if (preg_match('/^[a-zA-Z0-9]$/', substr($this->string, $this->position, 1))) {
                    $_success = true;
                    $this->value = substr($this->string, $this->position, 1);
                    $this->position += 1;
                } else {
                    $_success = false;
                }

                if (!$_success) {
                    break;
                }

                $_value18[] = $this->value;
            }

            if (!$this->cut) {
                $_success = true;
                $this->position = $_position17;
                $this->value = $_value18;
            }

            $this->cut = $_cut19;
        }

        if ($_success) {
            $s = $this->value;
        }

        if ($_success) {
            $this->value = call_user_func(function () use (&$s) {
                return implode('', $s);
            });
        }

        $this->cache['Identifier'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, 'Identifier');
        }

        return $_success;
    }

    protected function parseStr()
    {
        $_position = $this->position;

        if (isset($this->cache['Str'][$_position])) {
            $_success = $this->cache['Str'][$_position]['success'];
            $this->position = $this->cache['Str'][$_position]['position'];
            $this->value = $this->cache['Str'][$_position]['value'];

            return $_success;
        }

        $_position25 = $this->position;
        $_cut26 = $this->cut;

        $this->cut = false;
        $_success = $this->parseIdentifier();

        if (!$_success && !$this->cut) {
            $this->position = $_position25;

            $_value24 = array();

            if (substr($this->string, $this->position, strlen("'")) === "'") {
                $_success = true;
                $this->value = substr($this->string, $this->position, strlen("'"));
                $this->position += strlen("'");
            } else {
                $_success = false;

                $this->report($this->position, '"\'"');
            }

            if ($_success) {
                $_value24[] = $this->value;

                $_success = $this->parseChars();

                if ($_success) {
                    $head = $this->value;
                }
            }

            if ($_success) {
                $_value24[] = $this->value;

                $_value22 = array();
                $_cut23 = $this->cut;

                while (true) {
                    $_position21 = $this->position;

                    $this->cut = false;
                    $_value20 = array();

                    $_success = $this->parse_();

                    if ($_success) {
                        $_value20[] = $this->value;

                        $_success = $this->parseChars();

                        if ($_success) {
                            $r = $this->value;
                        }
                    }

                    if ($_success) {
                        $_value20[] = $this->value;

                        $this->value = $_value20;
                    }

                    if ($_success) {
                        $this->value = call_user_func(function () use (&$head, &$r) {
                            return " ".$r;
                        });
                    }

                    if (!$_success) {
                        break;
                    }

                    $_value22[] = $this->value;
                }

                if (!$this->cut) {
                    $_success = true;
                    $this->position = $_position21;
                    $this->value = $_value22;
                }

                $this->cut = $_cut23;

                if ($_success) {
                    $tail = $this->value;
                }
            }

            if ($_success) {
                $_value24[] = $this->value;

                if (substr($this->string, $this->position, strlen("'")) === "'") {
                    $_success = true;
                    $this->value = substr($this->string, $this->position, strlen("'"));
                    $this->position += strlen("'");
                } else {
                    $_success = false;

                    $this->report($this->position, '"\'"');
                }
            }

            if ($_success) {
                $_value24[] = $this->value;

                $this->value = $_value24;
            }

            if ($_success) {
                $this->value = call_user_func(function () use (&$head, &$r, &$tail) {
                    return $head.implode(' ', $tail);
                });
            }
        }

        $this->cut = $_cut26;

        $this->cache['Str'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, 'Str');
        }

        return $_success;
    }

    protected function parseChars()
    {
        $_position = $this->position;

        if (isset($this->cache['Chars'][$_position])) {
            $_success = $this->cache['Chars'][$_position]['success'];
            $this->position = $this->cache['Chars'][$_position]['position'];
            $this->value = $this->cache['Chars'][$_position]['value'];

            return $_success;
        }

        if (preg_match('/^[^\']$/', substr($this->string, $this->position, 1))) {
            $_success = true;
            $this->value = substr($this->string, $this->position, 1);
            $this->position += 1;
        } else {
            $_success = false;
        }

        if ($_success) {
            $_value28 = array($this->value);
            $_cut29 = $this->cut;

            while (true) {
                $_position27 = $this->position;

                $this->cut = false;
                if (preg_match('/^[^\']$/', substr($this->string, $this->position, 1))) {
                    $_success = true;
                    $this->value = substr($this->string, $this->position, 1);
                    $this->position += 1;
                } else {
                    $_success = false;
                }

                if (!$_success) {
                    break;
                }

                $_value28[] = $this->value;
            }

            if (!$this->cut) {
                $_success = true;
                $this->position = $_position27;
                $this->value = $_value28;
            }

            $this->cut = $_cut29;
        }

        if ($_success) {
            $s = $this->value;
        }

        if ($_success) {
            $this->value = call_user_func(function () use (&$s) {
                return implode('', $s);
            });
        }

        $this->cache['Chars'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, 'Chars');
        }

        return $_success;
    }

    protected function parse_()
    {
        $_position = $this->position;

        if (isset($this->cache['_'][$_position])) {
            $_success = $this->cache['_'][$_position]['success'];
            $this->position = $this->cache['_'][$_position]['position'];
            $this->value = $this->cache['_'][$_position]['value'];

            return $_success;
        }

        if (substr($this->string, $this->position, strlen(" ")) === " ") {
            $_success = true;
            $this->value = substr($this->string, $this->position, strlen(" "));
            $this->position += strlen(" ");
        } else {
            $_success = false;

            $this->report($this->position, '" "');
        }

        if ($_success) {
            $_value31 = array($this->value);
            $_cut32 = $this->cut;

            while (true) {
                $_position30 = $this->position;

                $this->cut = false;
                if (substr($this->string, $this->position, strlen(" ")) === " ") {
                    $_success = true;
                    $this->value = substr($this->string, $this->position, strlen(" "));
                    $this->position += strlen(" ");
                } else {
                    $_success = false;

                    $this->report($this->position, '" "');
                }

                if (!$_success) {
                    break;
                }

                $_value31[] = $this->value;
            }

            if (!$this->cut) {
                $_success = true;
                $this->position = $_position30;
                $this->value = $_value31;
            }

            $this->cut = $_cut32;
        }

        $this->cache['_'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, '_');
        }

        return $_success;
    }

    private function line()
    {
        return count(explode("\n", substr($this->string, 0, $this->position)));
    }

    private function rest()
    {
        return '"' . substr($this->string, $this->position) . '"';
    }

    protected function report($position, $expecting)
    {
        if ($this->cut) {
            $this->errors[$position][] = $expecting;
        } else {
            $this->warnings[$position][] = $expecting;
        }
    }

    private function expecting()
    {
        if (!empty($this->errors)) {
            ksort($this->errors);

            return end($this->errors)[0];
        }

        ksort($this->warnings);

        return implode(', ', end($this->warnings));
    }

    public function parse($_string)
    {
        $this->string = $_string;
        $this->position = 0;
        $this->value = null;
        $this->cache = array();
        $this->cut = false;
        $this->errors = array();
        $this->warnings = array();

        $_success = $this->parseQuery();

        if (!$_success) {
            throw new \InvalidArgumentException("Syntax error, expecting {$this->expecting()} on line {$this->line()}");
        }

        if ($this->position < strlen($this->string)) {
            throw new \InvalidArgumentException("Syntax error, unexpected {$this->rest()} on line {$this->line()}");
        }

        return $this->value;
    }
}