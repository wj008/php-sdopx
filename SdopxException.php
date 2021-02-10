<?php

namespace sdopx;

use Exception;
use Throwable;

/**
 * 模板错误
 * Class SdopxException
 * @package sdopx
 */
class SdopxException extends Exception
{
    protected string $detail = '';

    /**
     * SdopxException constructor.
     * @param string $message
     * @param string $detail
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '',string $detail = '', int $code = 0, Throwable $previous = null)
    {
        $this->detail = $detail;
        parent::__construct($message, $code, $previous);
    }

    public function getDetail(): string
    {
        return $this->detail;
    }
}