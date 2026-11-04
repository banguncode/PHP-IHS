<?php

namespace BangunCode\Ihs;

use BangunCode\Ihs\Fhir\Payload;

/**
 * Client for the on premise SATUSEHAT DICOM Router.
 *
 * Radiology orders are sent twice: once to SATUSEHAT and once to the DICOM Router, which turns the
 * ServiceRequest into a Modality Worklist entry. After the modality sends its images, look the
 * resulting study up with Client::imagingStudy()->findByAccessionNumber().
 */
final class DicomRouter
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @param Client $client
     * @param string $baseUrl FHIR base URL of the router, for example http://10.0.0.5:8080/fhir.
     */
    public function __construct(Client $client, $baseUrl)
    {
        $this->client = $client;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * POST {dicom_router_url}/ServiceRequest
     *
     * The payload must carry the same identifiers as the ServiceRequest sent to SATUSEHAT,
     * including the accession number (system http://sys-ids.kemkes.go.id/acsn/{organizationId}).
     *
     * @param array|string|\JsonSerializable $serviceRequest
     *
     * @return array|null
     */
    public function createServiceRequest($serviceRequest)
    {
        $serviceRequest = Payload::toArray($serviceRequest);
        if (!isset($serviceRequest['resourceType'])) {
            $serviceRequest = array_merge(array('resourceType' => 'ServiceRequest'), $serviceRequest);
        }

        return $this->client->request('POST', $this->baseUrl . '/ServiceRequest', $serviceRequest);
    }
}
