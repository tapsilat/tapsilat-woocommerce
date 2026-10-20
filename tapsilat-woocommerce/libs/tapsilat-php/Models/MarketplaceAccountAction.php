<?php

namespace Tapsilat\Models;

/**
 * Confirms a listed account action using the account's latest revision and environment.
 */
class MarketplaceAccountAction extends Model
{
    /** create, retry, resubmit, update, retrieve or retry_sync. */
    public $action;
    public $environment;
    /** The account's revision, exactly as received. */
    public $revision;
}
