<?php

namespace PeacefulBit\LispMachine\VM;

use function PeacefulBit\LispMachine\Environment\get;
use function PeacefulBit\LispMachine\Environment\makeEnvironment;

use PeacefulBit\LispMachine\Lexer;

const TOKEN_OR  = 'or';
const TOKEN_AND = 'and';
const TOKEN_NOT = 'not';

/**
 * @param $expression
 * @return mixed
 */
function evaluate($expression)
{
    return array_reduce($expression, function ($env, $expression) {
        return evaluateExpression($env, $expression);
    }, makeEnvironment());
}

/**
 * Evaluate single expression.
 *
 * @param $env
 * @param $expression
 * @return mixed
 * @throws VMException
 */
function evaluateExpression($env, $expression)
{
    if (Lexer\isLexeme($expression)) {
        return evaluateLexeme($env, $expression);
    }

    if (sizeof($expression) == 0) {
        throw new VMException("Empty expression");
    }

    return apply($env, $expression);
}

/**
 * Evaluate single lexeme.
 *
 * @param $env
 * @param $lexeme
 * @return mixed
 * @throws VMException
 */
function evaluateLexeme($env, $lexeme)
{
    $type = Lexer\getType($lexeme);
    switch ($type) {
        case Lexer\LEXEME_SYMBOL:
            $data = Lexer\getValue($lexeme);
            if (is_numeric($data)) {
                return is_float($data) ? floatval($data) : intval($data);
            }
            return get($env, $data);
        case Lexer\LEXEME_STRING:
            return Lexer\getValue($lexeme);
        default:
            throw new VMException("Unexpected token");
    }
}

/**
 * @param $env
 * @param $expression
 * @return mixed
 * @throws VMException
 */
function apply($env, $expression)
{
    $head = $expression[0];
    $type = Lexer\getType($head);

    if ($type != Lexer\LEXEME_SYMBOL) {
        throw new VMException("Function name must be a symbol");
    }

    $symbol = Lexer\getValue($head);
    $arguments = array_slice($expression, 1);

    return runCoreFunction($env, $symbol, $arguments);
}

/**
 * Runs core function.
 *
 * @param $env
 * @param $name
 * @param $arguments
 * @return mixed
 */
function runCoreFunction($env, $name, array $arguments)
{
    $modules = array_merge(
        \PeacefulBit\LispMachine\VM\Core\Math\export(),
        \PeacefulBit\LispMachine\VM\Core\Logical\export()
    );
    return call_user_func($modules[$name], $env, $arguments);
}