<?php

namespace BangunCode\Ihs\Service;

use BangunCode\Ihs\Exception\InvalidArgumentException;

/**
 * Kamus Farmasi dan Alat Kesehatan (KFA), the national drug and medical device dictionary.
 */
class Kfa extends ApiService
{
    /**
     * GET /kfa-v2/products?identifier={identifier}&code={code}
     *
     * @param string      $code
     * @param string      $identifier   kfa, lkpp or nie.
     * @param string|null $templateCode Narrows a search by template (92xxx for drugs, 82xxx for devices).
     *
     * @return array {"search_code", "search_identifier", "result"}
     */
    public function product($code, $identifier = 'kfa', $templateCode = null)
    {
        if (!in_array($identifier, array('kfa', 'lkpp', 'nie'), true)) {
            throw new InvalidArgumentException('KFA identifier must be "kfa", "lkpp" or "nie".');
        }

        return $this->get('/kfa-v2/products', array(
            'identifier' => $identifier,
            'code' => $code,
            'template_code' => $templateCode,
        ));
    }

    /**
     * GET /kfa-v2/products/all?page={page}&size={size}&product_type={productType}
     *
     * @param string                $productType farmasi or alkes.
     * @param int                   $page
     * @param int                   $size
     * @param array<string, string> $filters     Optional from_date, to_date, farmalkes_type, keyword,
     *                                           template_code and packaging_code.
     *
     * @return array {"total", "page", "size", "items": {"data"}}
     */
    public function products($productType, $page = 1, $size = 100, array $filters = array())
    {
        if (!in_array($productType, array('farmasi', 'alkes'), true)) {
            throw new InvalidArgumentException('KFA product type must be "farmasi" or "alkes".');
        }

        return $this->get('/kfa-v2/products/all', array_merge(array(
            'page' => (int) $page,
            'size' => (int) $size,
            'product_type' => $productType,
        ), $filters));
    }

    /**
     * GET /kfa/farmalkes-price-jkn?page={page}&limit={limit}&kfa_code={kfaCode}, JKN reference prices.
     *
     * @param string                $kfaCode Template code (92xxxxxx).
     * @param int                   $page
     * @param int                   $limit
     * @param array<string, string> $filters Optional region_code (regional1 to regional8) and document_ref.
     *
     * @return array
     */
    public function jknPrices($kfaCode, $page = 1, $limit = 10, array $filters = array())
    {
        return $this->get('/kfa/farmalkes-price-jkn', array_merge(array(
            'page' => (int) $page,
            'limit' => (int) $limit,
            'kfa_code' => $kfaCode,
        ), $filters));
    }

    /**
     * POST /kfa-v3/alkes/template, medical device templates.
     *
     * @param array<string, mixed> $criteria page and size (default 1 and 10) plus optional state,
     *                                       active, kfa_code, reference_code, search, updated_from_date,
     *                                       updated_to_date, farmalkes_type, category_code,
     *                                       sub_category_code, type_code and sub_type_code.
     *
     * @return array
     */
    public function deviceTemplates(array $criteria = array())
    {
        return $this->post('/kfa-v3/alkes/template', array_merge(array('page' => 1, 'size' => 10), $criteria));
    }

    /**
     * POST /kfa-v3/alkes/products, medical device product variants.
     *
     * @param array<string, mixed> $criteria Same fields as deviceTemplates().
     *
     * @return array
     */
    public function deviceProducts(array $criteria = array())
    {
        return $this->post('/kfa-v3/alkes/products', array_merge(array('page' => 1, 'size' => 10), $criteria));
    }
}
