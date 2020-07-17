<?php

namespace CapMousse\ReactRestify\Errors\Container;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
