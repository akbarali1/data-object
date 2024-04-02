<?php
declare(strict_types=1);

namespace Akbarali\DataObject;

use Throwable;

class DataObjectException extends \Exception
{
    /**
     * OperationException constructor.
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public const INVALID_MODEL_TYPE = -1000;

}
