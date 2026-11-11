<?php

namespace BangunCode\Ihs\Service;

use BangunCode\Ihs\Exception\InvalidArgumentException;

/**
 * Region and health facility master data.
 *
 * Responses are returned as decoded arrays. Region lists use {"status", "error", "message", "data"}
 * and the paginated version adds "meta" with page and cursor information.
 */
class MasterData extends ApiService
{
    /**
     * @var string[]
     */
    private static $levels = array('provinces', 'cities', 'districts', 'sub-districts');

    /**
     * GET /masterdata/v1/provinces?codes={codes}
     *
     * @param string[] $codes Province codes, for example array('11', '12').
     *
     * @return array
     */
    public function provinces(array $codes)
    {
        return $this->get('/masterdata/v1/provinces', array('codes' => implode(',', $codes)));
    }

    /**
     * GET /masterdata/v1/cities?province_codes={provinceCodes}
     *
     * @param string[] $provinceCodes
     *
     * @return array
     */
    public function cities(array $provinceCodes)
    {
        return $this->get('/masterdata/v1/cities', array('province_codes' => implode(',', $provinceCodes)));
    }

    /**
     * GET /masterdata/v1/districts?city_codes={cityCodes}
     *
     * @param string[] $cityCodes
     *
     * @return array
     */
    public function districts(array $cityCodes)
    {
        return $this->get('/masterdata/v1/districts', array('city_codes' => implode(',', $cityCodes)));
    }

    /**
     * GET /masterdata/v1/sub-districts?district_codes={districtCodes}, the villages (kelurahan/desa).
     *
     * @param string[] $districtCodes
     *
     * @return array
     */
    public function subDistricts(array $districtCodes)
    {
        return $this->get('/masterdata/v1/sub-districts', array('district_codes' => implode(',', $districtCodes)));
    }

    /**
     * GET /masterdata/v2/{level}?current_page={page}&next={next}&prev={previous}, one page of regions.
     *
     * @param string   $level    provinces, cities, districts or sub-districts.
     * @param int      $page
     * @param string   $next     Cursor from meta.cursors.next of the previous page.
     * @param string   $previous Cursor from meta.cursors.previous of the previous page.
     * @param string[] $codes    Optional region codes to filter on.
     *
     * @return array
     */
    public function regionPage($level, $page = 1, $next = '', $previous = '', array $codes = array())
    {
        if (!in_array($level, self::$levels, true)) {
            throw new InvalidArgumentException('Region level must be one of: ' . implode(', ', self::$levels) . '.');
        }

        return $this->get('/masterdata/v2/' . $level, array(
            'current_page' => (int) $page,
            'next' => (string) $next,
            'prev' => (string) $previous,
            'codes' => $codes ? implode(',', $codes) : null,
        ));
    }

    /**
     * GET /masterdata/v1/mastersaranaindex/mastersarana, the health facility index.
     *
     * @param string                $facilityType jenis_sarana code, for example 138.
     * @param int                   $page
     * @param int                   $limit
     * @param array<string, string> $filters      Optional kode_satusehat, kode_sarana, lower_bound_updated_at
     *                                            and upper_bound_updated_at (YYYY-MM-DD).
     *
     * @return array {"status_code", "message", "page", "total_page", "data"}
     */
    public function facilities($facilityType, $page = 1, $limit = 10, array $filters = array())
    {
        return $this->get('/masterdata/v1/mastersaranaindex/mastersarana', array_merge(array(
            'limit' => (int) $limit,
            'page' => (int) $page,
            'jenis_sarana' => $facilityType,
        ), $filters));
    }
}
