<?php

namespace Tapsilat\Concerns;

use Tapsilat\Models\MarketplaceAccountAction;
use Tapsilat\Models\MarketplaceApprovalPolicyInput;
use Tapsilat\Models\MarketplaceApprovalSettings;
use Tapsilat\Models\MarketplaceContractFilter;
use Tapsilat\Models\MarketplacePayoutApproval;
use Tapsilat\Models\MarketplacePayoutFilter;
use Tapsilat\Models\MarketplacePayoutOperationInput;
use Tapsilat\Models\MarketplaceProfileUpdate;
use Tapsilat\Models\MarketplaceReleasePolicyInput;
use Tapsilat\Models\MarketplaceSubmerchantCreateRequest;

/**
 * Marketplace sellers, payouts and policies. These routes are served by API v2, derived from
 * the configured base URL. Seller and payout revisions are opaque strings: send them back
 * exactly as received.
 */
trait MarketplaceMethods
{
    /**
     * Creates a logical seller; inspect "provisionings" for each VPOS account's readiness.
     */
    public function createMarketplaceSubmerchant(MarketplaceSubmerchantCreateRequest $request)
    {
        return $this->makeRequest('POST', $this->v2Url('/submerchants'), null, $request->toArray());
    }

    /**
     * Returns a page of the seller directory, or an exact external ID match when $externalId is set.
     */
    public function listMarketplaceSubmerchants($page = 1, $perPage = 20, $externalId = null)
    {
        return $this->makeRequest('GET', $this->v2Url('/submerchants'), [
            'page' => $page,
            'per_page' => $perPage,
            'external_id' => $externalId,
        ]);
    }

    /**
     * Returns the current seller profile and its revision.
     */
    public function getMarketplaceSubmerchant($id)
    {
        return $this->makeRequest('GET', $this->v2Url($this->path('/submerchants/%s', $id)));
    }

    /**
     * Replaces the profile at the supplied revision and reports whether review is required.
     */
    public function updateMarketplaceSubmerchant($id, MarketplaceProfileUpdate $request)
    {
        $url = $this->v2Url($this->path('/submerchants/%s', $id));
        return $this->makeRequest('PATCH', $url, null, $request->toArray());
    }

    /**
     * Returns the selected accounts' requirements for a seller operation.
     */
    public function getMarketplaceSellerContracts(MarketplaceContractFilter $filter)
    {
        return $this->makeRequest('GET', $this->v2Url('/marketplace/seller-contracts'), [
            'currency_id' => $filter->currency_id,
            'seller_type' => $filter->seller_type,
            'operation' => $filter->operation !== null ? $filter->operation : 'create',
            'vpos_id' => $filter->vpos_ids !== null ? array_values((array)$filter->vpos_ids) : null,
        ]);
    }

    /**
     * Returns account registration, synchronization and available actions.
     */
    public function getMarketplaceSellerAccounts($id)
    {
        return $this->makeRequest('GET', $this->v2Url($this->path('/submerchants/%s/providers', $id)));
    }

    /**
     * Executes an available account action at the supplied revision and environment.
     */
    public function runMarketplaceSellerAccountAction($id, $vposId, MarketplaceAccountAction $request)
    {
        $path = $this->path('/submerchants/%s/providers/%s/actions', $id, $vposId);
        return $this->makeRequest('POST', $this->v2Url($path), null, $request->toArray());
    }

    /**
     * Returns filtered organization-scoped payouts and their release state.
     */
    public function listMarketplacePayouts(MarketplacePayoutFilter $filter)
    {
        return $this->makeRequest('GET', $this->v2Url('/submerchant-payouts'), $filter->toArray());
    }

    /**
     * Returns a payout's eligibility, approval and settlement state.
     */
    public function getMarketplacePayout($id)
    {
        return $this->makeRequest('GET', $this->v2Url($this->path('/submerchant-payouts/%s', $id)));
    }

    /**
     * Submits eligible payouts to the approval workflow; the server answers 202 Accepted.
     */
    public function approveMarketplacePayouts(MarketplacePayoutApproval $request)
    {
        return $this->makeRequest('POST', $this->v2Url('/submerchant-payouts/approve'), null, $request->toArray());
    }

    /**
     * Changes allocation or withdraws approval; inspect "status" before treating it as complete.
     */
    public function runMarketplacePayoutOperation($payoutId, MarketplacePayoutOperationInput $request)
    {
        $path = $this->path('/submerchant-payouts/%s/operations', $payoutId);
        return $this->makeRequest('POST', $this->v2Url($path), null, $request->toArray());
    }

    public function getMarketplacePayoutOperation($id)
    {
        return $this->makeRequest('GET', $this->v2Url($this->path('/marketplace/payout-operations/%s', $id)));
    }

    /**
     * Checks the provider result without repeating a mutation.
     */
    public function reconcileMarketplacePayoutOperation($id)
    {
        $url = $this->v2Url($this->path('/marketplace/payout-operations/%s/reconcile', $id));
        return $this->makeRequest('POST', $url);
    }

    /**
     * Repeats an unresolved change only after verifying the previous provider state.
     */
    public function retryMarketplacePayoutOperation($id)
    {
        return $this->makeRequest('POST', $this->v2Url($this->path('/marketplace/payout-operations/%s/retry', $id)));
    }

    public function getMarketplaceApprovalSettings()
    {
        return $this->makeRequest('GET', $this->v2Url('/marketplace/approval-settings'));
    }

    public function updateMarketplaceApprovalSettings(MarketplaceApprovalSettings $request)
    {
        return $this->makeRequest('PATCH', $this->v2Url('/marketplace/approval-settings'), null, $request->toArray());
    }

    public function listMarketplaceApprovalPolicies()
    {
        return $this->makeRequest('GET', $this->v2Url('/marketplace/approval-policies'));
    }

    public function getMarketplaceApprovalPolicy($id)
    {
        return $this->makeRequest('GET', $this->v2Url($this->path('/marketplace/approval-policies/%s', $id)));
    }

    /**
     * Creates an approval rule. A currency_id given as a unit such as "TRY" is resolved to its UUID.
     */
    public function createMarketplaceApprovalPolicy(MarketplaceApprovalPolicyInput $request)
    {
        $url = $this->v2Url('/marketplace/approval-policies');
        return $this->makeRequest('POST', $url, null, $this->approvalPolicyPayload($request));
    }

    /**
     * Replaces an approval rule. A currency_id given as a unit such as "TRY" is resolved to its UUID.
     */
    public function updateMarketplaceApprovalPolicy($id, MarketplaceApprovalPolicyInput $request)
    {
        $url = $this->v2Url($this->path('/marketplace/approval-policies/%s', $id));
        return $this->makeRequest('PATCH', $url, null, $this->approvalPolicyPayload($request));
    }

    /**
     * Deletes an approval rule; the server answers 204 No Content.
     */
    public function deleteMarketplaceApprovalPolicy($id)
    {
        return $this->makeRequest('DELETE', $this->v2Url($this->path('/marketplace/approval-policies/%s', $id)));
    }

    public function listMarketplaceReleasePolicies()
    {
        return $this->makeRequest('GET', $this->v2Url('/marketplace/release-policies'));
    }

    public function getMarketplaceReleasePolicy($id)
    {
        return $this->makeRequest('GET', $this->v2Url($this->path('/marketplace/release-policies/%s', $id)));
    }

    public function createMarketplaceReleasePolicy(MarketplaceReleasePolicyInput $request)
    {
        return $this->makeRequest('POST', $this->v2Url('/marketplace/release-policies'), null, $request->toArray());
    }

    public function updateMarketplaceReleasePolicy($id, MarketplaceReleasePolicyInput $request)
    {
        $url = $this->v2Url($this->path('/marketplace/release-policies/%s', $id));
        return $this->makeRequest('PATCH', $url, null, $request->toArray());
    }

    /**
     * Deletes a release policy; the server answers 204 No Content.
     */
    public function deleteMarketplaceReleasePolicy($id)
    {
        return $this->makeRequest('DELETE', $this->v2Url($this->path('/marketplace/release-policies/%s', $id)));
    }

    private function approvalPolicyPayload(MarketplaceApprovalPolicyInput $request)
    {
        $payload = $request->toArray();
        if (isset($payload['currency_id']) && trim((string)$payload['currency_id']) !== '') {
            $payload['currency_id'] = $this->resolveCurrencyId($payload['currency_id']);
        }
        return $payload;
    }
}
