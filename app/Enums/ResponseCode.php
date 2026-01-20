<?php

namespace App\Enums;

enum ResponseCode: int
{
    case CREATED = 201;
    case SUCCESS = 200;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case VALIDATION_ERROR = 422;
    case SERVER_ERROR = 500;
}
