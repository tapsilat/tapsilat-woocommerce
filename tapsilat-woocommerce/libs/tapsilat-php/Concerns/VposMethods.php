<?php

namespace Tapsilat\Concerns;

use Tapsilat\Models\VposCreateRequest;
use Tapsilat\Models\VposSubmerchantCreateRequest;
use Tapsilat\Models\VposSubmerchantUpdateRequest;

/**
 * Virtual POS and VPOS submerchants.
 */
trait VposMethods
{
    public function listVpos($page = 1, $perPage = 10, $suborganizationId = null)
    {
        return $this->makeRequest('GET', '/vpos', [
            'page' => $page,
            'per_page' => $perPage,
            'suborganization_id' => $suborganizationId,
        ]);
    }

    /**
     * Creates a VPOS. currencies accepts currency UUIDs or units such as "TRY".
     */
    public function createVpos(VposCreateRequest $request)
    {
        return $this->makeRequest('POST', '/vpos', null, $request->toArray());
    }

    public function getVpos($id)
    {
        return $this->makeRequest('GET', $this->path('/vpos/%s', $id));
    }

    /**
     * Replaces a VPOS configuration: read it with getVpos() and send every field back, since
     * fields left out are stored as empty or false.
     */
    public function updateVpos($id, VposCreateRequest $request)
    {
        return $this->makeRequest('PATCH', $this->path('/vpos/%s', $id), null, $request->toArray());
    }

    public function deleteVpos($id)
    {
        return $this->makeRequest('DELETE', $this->path('/vpos/%s', $id));
    }

    public function listVposAcquirers()
    {
        return $this->makeRequest('GET', '/vpos/acquirers');
    }

    public function listCardSchemes()
    {
        return $this->makeRequest('GET', '/vpos/card-schemes');
    }

    public function listVposAcquirerTemplates()
    {
        return $this->makeRequest('GET', '/vpos/acquirer-templates');
    }

    public function listVposSubmerchants($page = 1, $perPage = 10, $vposId = null, $externalReferenceId = null)
    {
        return $this->makeRequest('GET', '/vpos-submerchant', [
            'page' => $page,
            'per_page' => $perPage,
            'vpos_id' => $vposId,
            'external_reference_id' => $externalReferenceId,
        ]);
    }

    public function createVposSubmerchant(VposSubmerchantCreateRequest $request)
    {
        return $this->makeRequest('POST', '/vpos-submerchant', null, $request->toArray());
    }

    /**
     * Returns a VPOS submerchant; the server answers 404 when it does not exist.
     */
    public function getVposSubmerchant($id)
    {
        return $this->makeRequest('GET', $this->path('/vpos-submerchant/%s', $id));
    }

    public function updateVposSubmerchant($id, VposSubmerchantUpdateRequest $request)
    {
        return $this->makeRequest('PATCH', $this->path('/vpos-submerchant/%s', $id), null, $request->toArray());
    }

    public function deleteVposSubmerchant($id)
    {
        return $this->makeRequest('DELETE', $this->path('/vpos-submerchant/%s', $id));
    }
}
