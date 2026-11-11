<?php

namespace BangunCode\Ihs\Service;

/**
 * KPTL procedure code master data.
 *
 * Every call posts {"method", "query_string", ...} with the header X-Encryption-Disabled: true so the
 * response is returned as plain JSON.
 */
class Kptl extends ApiService
{
    /**
     * POST /code with method get_all_code.
     *
     * @param string $query  Code or text to search for.
     * @param int    $offset
     * @param int    $limit
     *
     * @return array|null
     */
    public function codes($query, $offset = 0, $limit = 5)
    {
        return $this->call('/code', 'get_all_code', $query, array('offset' => (int) $offset, 'limit' => (int) $limit));
    }

    /**
     * POST /base_code with method get_all_base_code.
     *
     * @param string $query
     * @param int    $offset
     * @param int    $limit
     *
     * @return array|null
     */
    public function baseCodes($query, $offset = 0, $limit = 5)
    {
        return $this->call('/base_code', 'get_all_base_code', $query, array('offset' => (int) $offset, 'limit' => (int) $limit));
    }

    /**
     * POST /base_code_combination with method get_modifiers, the modifiers allowed for a base code.
     *
     * @param string $baseCode
     * @param int    $maxIteration
     *
     * @return array|null
     */
    public function baseCodeCombinations($baseCode, $maxIteration = 2)
    {
        return $this->call('/base_code_combination', 'get_modifiers', $baseCode, array('max_iteration' => (int) $maxIteration));
    }

    /**
     * POST /modifier with method get_modifier.
     *
     * @param string $query  Empty to list every modifier.
     * @param int    $offset
     * @param int    $limit
     *
     * @return array|null
     */
    public function modifiers($query = '', $offset = 0, $limit = 50)
    {
        return $this->call('/modifier', 'get_modifier', $query, array('offset' => (int) $offset, 'limit' => (int) $limit));
    }

    /**
     * POST /modifier_value with method get_modifier_value.
     *
     * @param string $modifierCode For example M006.
     *
     * @return array|null
     */
    public function modifierValues($modifierCode)
    {
        return $this->call('/modifier_value', 'get_modifier_value', $modifierCode);
    }

    /**
     * POST /base_code_by_modifier with method get_base_code.
     *
     * @param string $modifierCode For example M110.
     * @param int    $offset
     * @param int    $limit
     *
     * @return array|null
     */
    public function baseCodesByModifier($modifierCode, $offset = 0, $limit = 10)
    {
        return $this->call('/base_code_by_modifier', 'get_base_code', $modifierCode, array('offset' => (int) $offset, 'limit' => (int) $limit));
    }

    /**
     * @param string               $path
     * @param string               $method
     * @param string               $query
     * @param array<string, mixed> $extra
     *
     * @return array|null
     */
    private function call($path, $method, $query, array $extra = array())
    {
        return $this->post(
            $path,
            array_merge(array('method' => $method, 'query_string' => (string) $query), $extra),
            array('X-Encryption-Disabled' => 'true')
        );
    }
}
