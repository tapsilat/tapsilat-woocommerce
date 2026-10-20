<?php

namespace Tapsilat\Concerns;

use Tapsilat\Models\CardInstallmentsRequest;
use Tapsilat\Models\CardTokenizeRequest;
use Tapsilat\Models\InstallmentTableRequest;

/**
 * Card installment options and saved cards (tokenization).
 */
trait CardMethods
{
    /**
     * Returns the installment options and commission rates for a card BIN and amount.
     */
    public function getCardInstallments(CardInstallmentsRequest $request)
    {
        return $this->makeRequest('GET', '/card/installments', [
            'bin' => $request->bin,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'is_three_d' => $request->is_three_d ? true : null,
        ]);
    }

    /**
     * Returns installment options grouped by bank card program, with an interest-free flag per row.
     */
    public function getInstallmentTable(InstallmentTableRequest $request)
    {
        return $this->makeRequest('GET', '/card/installment-table', [
            'amount' => $request->amount,
            'currency' => $request->currency,
            'is_three_d' => $request->is_three_d ? true : null,
        ]);
    }

    /**
     * Tokenizes a card and returns 3D secure form details when required.
     */
    public function tokenizeCard(CardTokenizeRequest $request)
    {
        return $this->makeRequest('POST', '/tokenization/card/tokenize', null, $request->toArray());
    }

    public function listSavedCards($page = 1, $perPage = 10)
    {
        return $this->makeRequest('GET', '/tokenization/card/list', ['page' => $page, 'per_page' => $perPage]);
    }

    public function deleteSavedCard($id)
    {
        return $this->makeRequest('DELETE', $this->path('/tokenization/card/%s', $id));
    }
}
