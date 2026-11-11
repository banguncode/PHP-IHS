<?php

namespace BangunCode\Ihs\Exception;

use BangunCode\Ihs\Fhir\OperationOutcome;
use BangunCode\Ihs\Http\Response;

/**
 * Thrown when SATUSEHAT answers with an error.
 *
 * SATUSEHAT reports errors as a FHIR OperationOutcome, an API gateway fault
 * ({"fault": {"faultstring", "detail": {"errorcode"}}}), an OAuth error ({"ErrorCode", "Error"}),
 * a JSON envelope used by Patient updates, master data and the national medical record
 * ({"code" or "status", "error" or "success", "message", "data"}), a validation list
 * ({"detail": [{"loc", "msg"}]}), plain text, HTML or an empty body. All of them are normalised
 * into a message and an error code.
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
            $detail = null;
            if (isset($body['message']) && is_string($body['message'])) {
                $detail = $body['message'];
            } elseif (isset($body['data']) && is_string($body['data'])) {
                $detail = $body['data'];
            }
            if (isset($body['data']['code']) && is_string($body['data']['code'])) {
                $errorCode = $body['data']['code'];
            }
            $messages = array();
            foreach (isset($body['data']['validation']) ? (array) $body['data']['validation'] : array() as $item) {
                if (isset($item['message'])) {
                    $messages[] = (string) $item['message'];
                }
            }
            if ($messages) {
                $detail .= ': ' . implode('; ', $messages);
            }
            foreach (array('code', 'status', 'status_code') as $field) {
                if (isset($body[$field]) && is_numeric($body[$field]) && (int) $body[$field] >= 400) {
                    $status = (int) $body[$field];
                    break;
                }
            }
        } elseif (is_array($body) && isset($body['detail']) && is_array($body['detail'])) {
            $messages = array();
            foreach ($body['detail'] as $item) {
                if (isset($item['msg'])) {
                    $location = isset($item['loc']) ? implode('.', (array) $item['loc']) . ': ' : '';
                    $messages[] = $location . $item['msg'];
                }
            }
            $detail = $messages ? implode('; ', $messages) : null;
        } else {
            $text = trim(preg_replace('/\s+/', ' ', strip_tags($response->getBody())));
            $detail = $text !== '' ? substr($text, 0, 500) : null;
        }

        $message = sprintf('SATUSEHAT responded with HTTP %d', $response->getStatusCode());
        if ($detail !== null && $detail !== '') {
            $message .= ': ' . $detail;
        }

        if (($outcome !== null && $outcome->hasIssueCode(array('suppressed', 'forbidden'))) || $errorCode === 'CONSENT_REQUIRED') {
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
     * Whether a decoded body is a non FHIR envelope carrying a boolean "error" or "success" flag.
     *
     * @param mixed $body
     *
     * @return bool
     */
    public static function isEnvelope($body)
    {
        return is_array($body)
            && ((isset($body['error']) && is_bool($body['error'])) || (isset($body['success']) && is_bool($body['success'])));
    }

    /**
     * Whether a decoded body is an envelope that reports a failure, even with an HTTP 2xx status.
     *
     * @param mixed $body
     *
     * @return bool
     */
    public static function isFailedEnvelope($body)
    {
        return self::isEnvelope($body)
            && ((isset($body['error']) && $body['error'] === true) || (isset($body['success']) && $body['success'] === false));
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * Machine readable code: the gateway errorcode, the OAuth ErrorCode, the envelope data.code
     * (for example CONSENT_REQUIRED) or the first OperationOutcome issue code.
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
