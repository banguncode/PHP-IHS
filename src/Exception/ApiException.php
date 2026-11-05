<?php

namespace BangunCode\Ihs\Exception;

use BangunCode\Ihs\Fhir\OperationOutcome;
use BangunCode\Ihs\Http\Response;

/**
 * Thrown when SATUSEHAT answers with an error.
 *
 * SATUSEHAT reports errors in four shapes: a FHIR OperationOutcome, an API gateway fault
 * ({"fault": {"faultstring", "detail": {"errorcode"}}}), an OAuth error ({"ErrorCode", "Error"})
 * and, for Patient updates, an envelope ({"code", "error", "message", "data": {"validation"}}).
 * Some errors carry a plain text or HTML body, or none at all. All of them are normalised into a
 * message and an error code.
 */
class ApiException extends \RuntimeException implements IhsException
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var string|null
     */
    private $errorCode;

    /**
     * @var OperationOutcome|null
     */
    private $outcome;

    /**
     * @param string                $message
     * @param Response              $response
     * @param string|null           $errorCode
     * @param OperationOutcome|null $outcome
     * @param \Exception|null       $previous
     */
    public function __construct($message, Response $response, $errorCode = null, $outcome = null, $previous = null)
    {
        parent::__construct($message, $response->getStatusCode(), $previous);
        $this->response = $response;
        $this->errorCode = $errorCode;
        $this->outcome = $outcome;
    }

    /**
     * Builds the most specific exception for an error response.
     *
     * @param Response $response
     *
     * @return ApiException
     */
    public static function fromResponse(Response $response)
    {
        $status = $response->getStatusCode();
        $body = $response->getJson();
        $outcome = null;
        $errorCode = null;
        $detail = null;

        if (OperationOutcome::isOperationOutcome($body)) {
            $outcome = new OperationOutcome($body);
            $detail = $outcome->getMessage();
            $first = $outcome->getIssues();
            $errorCode = $first ? $first[0]->getCode() : null;
        } elseif (is_array($body) && isset($body['fault']) && is_array($body['fault'])) {
            $detail = isset($body['fault']['faultstring']) ? (string) $body['fault']['faultstring'] : null;
            $errorCode = isset($body['fault']['detail']['errorcode'])
                ? (string) $body['fault']['detail']['errorcode']
                : null;
        } elseif (is_array($body) && isset($body['ErrorCode'])) {
            $errorCode = (string) $body['ErrorCode'];
            $detail = isset($body['Error']) ? (string) $body['Error'] : null;
        } elseif (self::isEnvelope($body)) {
            $detail = isset($body['message']) ? (string) $body['message'] : null;
            $messages = array();
            foreach (isset($body['data']['validation']) ? (array) $body['data']['validation'] : array() as $item) {
                if (isset($item['message'])) {
                    $messages[] = (string) $item['message'];
                }
            }
            if ($messages) {
                $detail .= ': ' . implode('; ', $messages);
            }
            if (isset($body['code']) && is_numeric($body['code']) && (int) $body['code'] >= 400) {
                $status = (int) $body['code'];
            }
        } else {
            $text = trim(preg_replace('/\s+/', ' ', strip_tags($response->getBody())));
            $detail = $text !== '' ? substr($text, 0, 500) : null;
        }

        $message = sprintf('SATUSEHAT responded with HTTP %d', $response->getStatusCode());
        if ($detail !== null && $detail !== '') {
            $message .= ': ' . $detail;
        }

        if ($outcome !== null && $outcome->hasIssueCode(array('suppressed', 'forbidden'))) {
            return new ConsentException($message, $response, $errorCode, $outcome);
        }
        if ($status === 401) {
            return new AuthenticationException($message, $response, $errorCode, $outcome);
        }
        if ($status === 403) {
            return new ForbiddenException($message, $response, $errorCode, $outcome);
        }
        if ($status === 404) {
            return new NotFoundException($message, $response, $errorCode, $outcome);
        }
        if ($status === 400 || $status === 422) {
            return new ValidationException($message, $response, $errorCode, $outcome);
        }
        if ($status >= 500) {
            return new ServerException($message, $response, $errorCode, $outcome);
        }

        return new self($message, $response, $errorCode, $outcome);
    }

    /**
     * Whether a decoded body is the non FHIR envelope {"code", "error", "message", "data"} that
     * SATUSEHAT uses for some Patient operations.
     *
     * @param mixed $body
     *
     * @return bool
     */
    public static function isEnvelope($body)
    {
        return is_array($body) && isset($body['error'], $body['message']) && is_bool($body['error']);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * Machine readable code: the gateway errorcode, the OAuth ErrorCode or the first
     * OperationOutcome issue code.
     *
     * @return string|null
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return OperationOutcome|null
     */
    public function getOperationOutcome()
    {
        return $this->outcome;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return string|null Value of the x-request-id header, useful when reporting issues to SATUSEHAT.
     */
    public function getRequestId()
    {
        return $this->response->getHeader('x-request-id');
    }
}
