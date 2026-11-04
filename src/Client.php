<?php

namespace BangunCode\Ihs;

use BangunCode\Ihs\Auth\OAuthTokenProvider;
use BangunCode\Ihs\Auth\TokenProviderInterface;
use BangunCode\Ihs\Auth\TokenStorageInterface;
use BangunCode\Ihs\Exception\ApiException;
use BangunCode\Ihs\Exception\AuthenticationException;
use BangunCode\Ihs\Exception\InvalidArgumentException;
use BangunCode\Ihs\Exception\TransportException;
use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;
use BangunCode\Ihs\Fhir\OperationOutcome;
use BangunCode\Ihs\Fhir\Payload;
use BangunCode\Ihs\Fhir\ResourceType;
use BangunCode\Ihs\Http\CurlHttpClient;
use BangunCode\Ihs\Http\HttpClientInterface;
use BangunCode\Ihs\Http\Request;
use BangunCode\Ihs\Http\Response;
use BangunCode\Ihs\Endpoint\DocumentReferenceEndpoint;
use BangunCode\Ihs\Endpoint\ImagingStudyEndpoint;
use BangunCode\Ihs\Endpoint\LocationEndpoint;
use BangunCode\Ihs\Endpoint\MedicationDispenseEndpoint;
use BangunCode\Ihs\Endpoint\MedicationRequestEndpoint;
use BangunCode\Ihs\Endpoint\PatientEndpoint;
use BangunCode\Ihs\Endpoint\PractitionerEndpoint;
use BangunCode\Ihs\Endpoint\ResourceEndpoint;

/**
 * Entry point for the SATUSEHAT FHIR R4 API.
 *
 * <code>
 * $client = new Client(new Config(array(
 *     'environment' => Environment::DEVELOPMENT,
 *     'client_id' => '...',
 *     'client_secret' => '...',
 *     'organization_id' => '...',
 * )));
 *
 * $patient = $client->patient()->findByNik('9271060312000001')->first();
 * $encounter = $client->encounter()->create($encounterResource);
 * </code>
 *
 * @method ResourceEndpoint allergyIntolerance()
 * @method ResourceEndpoint carePlan()
 * @method ResourceEndpoint clinicalImpression()
 * @method ResourceEndpoint composition()
 * @method ResourceEndpoint condition()
 * @method ResourceEndpoint device()
 * @method ResourceEndpoint deviceDispense()
 * @method ResourceEndpoint deviceRequest()
 * @method ResourceEndpoint deviceUseStatement()
 * @method ResourceEndpoint diagnosticReport()
 * @method ResourceEndpoint encounter()
 * @method ResourceEndpoint familyMemberHistory()
 * @method ResourceEndpoint goal()
 * @method ResourceEndpoint medication()
 * @method ResourceEndpoint medicationAdministration()
 * @method ResourceEndpoint medicationStatement()
 * @method ResourceEndpoint nutritionOrder()
 * @method ResourceEndpoint observation()
 * @method ResourceEndpoint organization()
 * @method ResourceEndpoint procedure()
 * @method ResourceEndpoint questionnaireResponse()
 * @method ResourceEndpoint riskAssessment()
 * @method ResourceEndpoint serviceRequest()
 * @method ResourceEndpoint specimen()
 * @method ResourceEndpoint supplyDelivery()
 * @method ResourceEndpoint supplyRequest()
 */
class Client
{
    /**
     * @var array<string, string>
     */
    private static $endpointClasses = array(
        ResourceType::PATIENT => 'BangunCode\Ihs\Endpoint\PatientEndpoint',
        ResourceType::PRACTITIONER => 'BangunCode\Ihs\Endpoint\PractitionerEndpoint',
        ResourceType::LOCATION => 'BangunCode\Ihs\Endpoint\LocationEndpoint',
        ResourceType::DOCUMENT_REFERENCE => 'BangunCode\Ihs\Endpoint\DocumentReferenceEndpoint',
        ResourceType::MEDICATION_REQUEST => 'BangunCode\Ihs\Endpoint\MedicationRequestEndpoint',
        ResourceType::MEDICATION_DISPENSE => 'BangunCode\Ihs\Endpoint\MedicationDispenseEndpoint',
        ResourceType::IMAGING_STUDY => 'BangunCode\Ihs\Endpoint\ImagingStudyEndpoint',
    );

    /**
     * @var Config
     */
    private $config;

    /**
     * @var HttpClientInterface
     */
    private $http;

    /**
     * @var TokenProviderInterface
     */
    private $tokens;

    /**
     * @var array<string, ResourceEndpoint>
     */
    private $endpoints = array();

    /**
     * @var DicomRouter|null
     */
    private $dicomRouter;

    /**
     * @var Response|null
     */
    private $lastResponse;

    /**
     * @param Config                                           $config
     * @param HttpClientInterface|null                         $http   Defaults to CurlHttpClient built from the http option.
     * @param TokenProviderInterface|TokenStorageInterface|null $tokens A token provider, or a storage for the default
     *                                                                 OAuth2 provider. Defaults to in-memory storage.
     */
    public function __construct(Config $config, $http = null, $tokens = null)
    {
        if ($http !== null && !$http instanceof HttpClientInterface) {
            throw new InvalidArgumentException('HTTP client must implement HttpClientInterface.');
        }
        $this->config = $config;
        $this->http = $http !== null ? $http : new CurlHttpClient($config->getHttpOptions());

        if ($tokens instanceof TokenProviderInterface) {
            $this->tokens = $tokens;
        } elseif ($tokens === null || $tokens instanceof TokenStorageInterface) {
            $this->tokens = new OAuthTokenProvider($config, $this->http, $tokens);
        } else {
            throw new InvalidArgumentException('Tokens must be a TokenProviderInterface or a TokenStorageInterface.');
        }
    }

    /**
     * Endpoint accessors for the resource types listed on this class, for example observation().
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return ResourceEndpoint
     */
    public function __call($name, array $arguments)
    {
        $type = ucfirst($name);
        if (!ResourceType::isKnown($type)) {
            throw new \BadMethodCallException(sprintf(
                'Call to undefined method %s::%s(). Use resource() for resource types outside ResourceType.',
                __CLASS__,
                $name
            ));
        }

        return $this->resource($type);
    }

    /**
     * Endpoint for any resource type, including types not listed in ResourceType.
     *
     * @param string $type For example "Immunization".
     *
     * @return ResourceEndpoint
     */
    public function resource($type)
    {
        if (!ResourceType::isValidName($type)) {
            throw new InvalidArgumentException(sprintf('Invalid resource type "%s".', $type));
        }
        if (!isset($this->endpoints[$type])) {
            $class = isset(self::$endpointClasses[$type]) ? self::$endpointClasses[$type] : 'BangunCode\Ihs\Endpoint\ResourceEndpoint';
            $this->endpoints[$type] = new $class($this, $type);
        }

        return $this->endpoints[$type];
    }

    /**
     * @return PatientEndpoint
     */
    public function patient()
    {
        return $this->resource(ResourceType::PATIENT);
    }

    /**
     * @return PractitionerEndpoint
     */
    public function practitioner()
    {
        return $this->resource(ResourceType::PRACTITIONER);
    }

    /**
     * @return LocationEndpoint
     */
    public function location()
    {
        return $this->resource(ResourceType::LOCATION);
    }

    /**
     * @return DocumentReferenceEndpoint
     */
    public function documentReference()
    {
        return $this->resource(ResourceType::DOCUMENT_REFERENCE);
    }

    /**
     * @return MedicationRequestEndpoint
     */
    public function medicationRequest()
    {
        return $this->resource(ResourceType::MEDICATION_REQUEST);
    }

    /**
     * @return MedicationDispenseEndpoint
     */
    public function medicationDispense()
    {
        return $this->resource(ResourceType::MEDICATION_DISPENSE);
    }

    /**
     * @return ImagingStudyEndpoint
     */
    public function imagingStudy()
    {
        return $this->resource(ResourceType::IMAGING_STUDY);
    }

    /**
     * Client for the on premise DICOM Router, configured through dicom_router_url.
     *
     * @return DicomRouter
     */
    public function dicomRouter()
    {
        if ($this->config->getDicomRouterUrl() === null) {
            throw new InvalidArgumentException('Set the dicom_router_url configuration option to use the DICOM Router.');
        }
        if ($this->dicomRouter === null) {
            $this->dicomRouter = new DicomRouter($this, $this->config->getDicomRouterUrl());
        }

        return $this->dicomRouter;
    }

    /**
     * POST a transaction Bundle to the FHIR base URL.
     *
     * The whole Bundle succeeds or fails as one unit. Use Bundle::getCreatedIds() on the result to
     * read the ids assigned to each entry.
     *
     * @param \BangunCode\Ihs\Fhir\TransactionBundle|array|string $bundle
     *
     * @return Bundle The transaction-response Bundle.
     */
    public function transaction($bundle)
    {
        $bundle = Payload::toArray($bundle);
        if (!isset($bundle['resourceType']) || $bundle['resourceType'] !== 'Bundle') {
            throw new InvalidArgumentException('A transaction payload must be a Bundle resource.');
        }

        return new Bundle((array) $this->request('POST', '', $bundle));
    }

    /**
     * Fetches the page referenced by the next link of a search Bundle.
     *
     * @param Bundle $bundle
     *
     * @return Bundle
     */
    public function nextPage(Bundle $bundle)
    {
        $url = $bundle->getLink('next');
        if ($url === null) {
            throw new InvalidArgumentException('The Bundle has no next page.');
        }

        return new Bundle((array) $this->request('GET', $url));
    }

    /**
     * Facility scoped identifier system for the configured organization, for example
     * identifierSystem('encounter') returns http://sys-ids.kemkes.go.id/encounter/{organization_id}.
     *
     * @param string $kind
     *
     * @return string
     */
    public function identifierSystem($kind)
    {
        if ($this->config->getOrganizationId() === null) {
            throw new InvalidArgumentException('Set the organization_id configuration option to build identifier systems.');
        }

        return IdentifierSystem::forOrganization($kind, $this->config->getOrganizationId());
    }

    /**
     * Sends an authenticated request and returns the decoded JSON body.
     *
     * A token rejected as expired or invalid is renewed and the request is sent once more.
     *
     * @param string                              $method
     * @param string                              $path    Relative to the FHIR base URL, or an absolute URL on the
     *                                                     FHIR or DICOM Router host.
     * @param array|string|\JsonSerializable|null $payload
     * @param array<string, string|int|string[]>  $query   An array value repeats the parameter.
     * @param array<string, string>               $headers
     *
     * @return array|null
     *
     * @throws ApiException       When SATUSEHAT answers with an error.
     * @throws TransportException When no response was received.
     */
    public function request($method, $path, $payload = null, array $query = array(), array $headers = array())
    {
        $url = $this->resolveUrl($path) . self::buildQuery($query);
        $body = $payload === null ? null : Payload::encode(Payload::toArray($payload));

        $headers = array_merge(array('Accept' => 'application/json'), $headers);
        if ($body !== null && !isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        for ($attempt = 1; ; $attempt++) {
            $headers['Authorization'] = 'Bearer ' . $this->tokens->getToken();
            $response = $this->http->send(new Request($method, $url, $headers, $body));
            $this->lastResponse = $response;

            if ($response->isSuccessful()) {
                return $this->decode($response);
            }

            $exception = ApiException::fromResponse($response);
            if ($attempt === 1 && $exception instanceof AuthenticationException && $exception->isTokenRejected()) {
                $this->tokens->invalidate();
                continue;
            }

            throw $exception;
        }
    }

    /**
     * The last HTTP response received, for reading headers such as ETag, Location or x-request-id.
     *
     * @return Response|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Response $response
     *
     * @return array|null
     */
    private function decode(Response $response)
    {
        if (trim($response->getBody()) === '') {
            return null;
        }

        $data = $response->getJson();
        if (!is_array($data)) {
            throw new ApiException('SATUSEHAT returned a response that is not JSON.', $response);
        }

        if (OperationOutcome::isOperationOutcome($data)) {
            $outcome = new OperationOutcome($data);
            if ($outcome->hasErrors() || $outcome->hasIssueCode(array('suppressed'))) {
                throw ApiException::fromResponse($response);
            }
        }

        return $data;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function resolveUrl($path)
    {
        $path = (string) $path;
        $base = $this->config->getBaseUrl();

        if (!preg_match('#^https?://#i', $path)) {
            return $path === '' ? $base : $base . '/' . ltrim($path, '/');
        }

        $allowed = array($base, $this->config->getDicomRouterUrl());
        foreach ($allowed as $root) {
            if ($root !== null && self::origin($root) === self::origin($path)) {
                return $path;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Refusing to send the access token to "%s": the URL is outside the configured SATUSEHAT hosts.',
            $path
        ));
    }

    /**
     * @param string $url
     *
     * @return string scheme://host:port in lower case.
     */
    private static function origin($url)
    {
        $parts = parse_url($url);
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);

        return $scheme . '://' . $host . ':' . $port;
    }

    /**
     * @param array<string, string|int|string[]> $query
     *
     * @return string Query string with a leading "?", or an empty string.
     */
    private static function buildQuery(array $query)
    {
        $pairs = array();
        foreach ($query as $name => $values) {
            foreach ((array) $values as $value) {
                if ($value === null) {
                    continue;
                }
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $pairs[] = rawurlencode($name) . '=' . rawurlencode((string) $value);
            }
        }

        return $pairs ? '?' . implode('&', $pairs) : '';
    }
}
