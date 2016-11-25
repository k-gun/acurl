<?php
declare(strict_types=1);

namespace ACurl\Http;

final class Response extends Stream
{
    /**
     * Informational constants.
     * @const int
     */
    const CONTINUE                         = 100,
          SWITCHING_PROTOCOLS              = 101,
          PROCESSING                       = 102;

    /**
     * Success constants.
     * @const int
     */
    const OK                               = 200,
          CREATED                          = 201,
          ACCEPTED                         = 202,
          NON_AUTHORITATIVE_INFORMATION    = 203,
          NO_CONTENT                       = 204,
          RESET_CONTENT                    = 205,
          PARTIAL_CONTENT                  = 206,
          MULTI_STATUS                     = 207,
          ALREADY_REPORTED                 = 208,
          IM_USED                          = 226;

    /**
     * Redirection constants.
     * @const int
     */
    const MULTIPLE_CHOICES                 = 300,
          MOVED_PERMANENTLY                = 301,
          FOUND                            = 302,
          SEE_OTHER                        = 303,
          NOT_MODIFIED                     = 304,
          USE_PROXY                        = 305,
          TEMPORARY_REDIRECT               = 307,
          PERMANENT_REDIRECT               = 308;

    /**
     * Client error constants.
     * @const int
     */
    const BAD_REQUEST                      = 400,
          UNAUTHORIZED                     = 401,
          PAYMENT_REQUIRED                 = 402,
          FORBIDDEN                        = 403,
          NOT_FOUND                        = 404,
          METHOD_NOT_ALLOWED               = 405,
          NOT_ACCEPTABLE                   = 406,
          PROXY_AUTHENTICATION_REQUIRED    = 407,
          REQUEST_TIMEOUT                  = 408,
          CONFLICT                         = 409,
          GONE                             = 410,
          LENGTH_REQUIRED                  = 411,
          PRECONDITION_FAILED              = 412,
          REQUEST_ENTITY_TOO_LARGE         = 413,
          REQUEST_URI_TOO_LONG             = 414,
          UNSUPPORTED_MEDIA_TYPE           = 415,
          REQUESTED_RANGE_NOT_SATISFIABLE  = 416,
          EXPECTATION_FAILED               = 417,
          I_M_A_TEAPOT                     = 418;

    /**
     * Server error constants.
     * @const int
     */
    const INTERNAL_SERVER_ERROR            = 500,
          NOT_IMPLEMENTED                  = 501,
          BAD_GATEWAY                      = 502,
          SERVICE_UNAVAILABLE              = 503,
          GATEWAY_TIMEOUT                  = 504,
          HTTP_VERSION_NOT_SUPPORTED       = 505,
          BANDWIDTH_LIMIT_EXCEEDED         = 509;

    final public function __construct()
    {
        $this->type = StreamInterface::TYPE_RESPONSE;
    }

    final public function getStatus()
    {
        return $this->getHeader('_status');
    }

    final public function getStatusCode()
    {
        return $this->getHeader('_status_code');
    }

    final public function getStatusText()
    {
        return $this->getHeader('_status_text');
    }
}
