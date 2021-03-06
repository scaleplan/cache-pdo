<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class QueryExecutionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class QueryExecutionException extends DbException
{
    public const MESSAGE = 'db.request-execution-error';
    public const CODE = 400;
}
