<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Exception\InvalidArgumentException;
use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;
use BangunCode\Ihs\Fhir\JsonPatch;

/**
 * Location lookups and inpatient bed status.
 */
class LocationEndpoint extends ResourceEndpoint
{
    const BED_STATUS_SYSTEM = 'http://terminology.hl7.org/CodeSystem/v2-0116';

    const BED_CLOSED = 'C';
    const BED_HOUSEKEEPING = 'H';
    const BED_ISOLATED = 'I';
    const BED_CONTAMINATED = 'K';
    const BED_OCCUPIED = 'O';
    const BED_UNOCCUPIED = 'U';

    /**
     * @var array<string, string>
     */
    private static $bedStatusDisplay = array(
        self::BED_CLOSED => 'Closed',
        self::BED_HOUSEKEEPING => 'Housekeeping',
        self::BED_ISOLATED => 'Isolated',
        self::BED_CONTAMINATED => 'Contaminated',
        self::BED_OCCUPIED => 'Occupied',
        self::BED_UNOCCUPIED => 'Unoccupied',
    );

    /**
     * GET /Location?identifier=http://sys-ids.kemkes.go.id/location/{organizationId}|{value}
     *
     * @param string      $value          The facility's own location code, for example G-2-R-1A.
     * @param string|null $organizationId Defaults to the organization_id configuration option.
     *
     * @return Bundle
     */
    public function findByIdentifier($value, $organizationId = null)
    {
        if ($organizationId === null) {
            $organizationId = $this->client->getConfig()->getOrganizationId();
        }
        if ($organizationId === null) {
            throw new InvalidArgumentException('An organization id is required: pass it or set organization_id.');
        }

        return $this->searchByIdentifier(IdentifierSystem::forOrganization('location', $organizationId), $value);
    }

    /**
     * GET /Location?name={name}
     *
     * @param string $name
     *
     * @return Bundle
     */
    public function findByName($name)
    {
        return $this->search(array('name' => $name));
    }

    /**
     * GET /Location?organization={organizationId}
     *
     * @param string $organizationId
     *
     * @return Bundle
     */
    public function findByOrganization($organizationId)
    {
        return $this->search(array('organization' => $organizationId));
    }

    /**
     * PATCH /Location/{id} setting operationalStatus, the occupancy of a bed.
     *
     * Mirrors the SATUSEHAT inpatient collection: "add" operations when the location has no
     * operationalStatus yet, "replace" operations when it already has one.
     *
     * @param string      $id
     * @param string      $code        One of the BED_* constants.
     * @param bool        $hasExisting Whether the location already carries an operationalStatus.
     * @param string|null $display     Defaults to the HL7 v2-0116 display for the code.
     *
     * @return array The patched Location.
     */
    public function setBedStatus($id, $code, $hasExisting = false, $display = null)
    {
        if ($display === null) {
            $display = isset(self::$bedStatusDisplay[$code]) ? self::$bedStatusDisplay[$code] : $code;
        }
        $values = array('system' => self::BED_STATUS_SYSTEM, 'code' => $code, 'display' => $display);

        $patch = JsonPatch::create();
        foreach ($values as $field => $value) {
            if ($hasExisting) {
                $patch->replace('/operationalStatus/' . $field, $value);
            } else {
                $patch->add('/operationalStatus/' . $field, $value);
            }
        }

        return $this->patch($id, $patch);
    }
}
