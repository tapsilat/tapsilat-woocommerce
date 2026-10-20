<?php

namespace Tapsilat\Models;

class MarketplaceReleasePolicyInput extends Model
{
    public $name;
    public $enabled;
    public $default;
    public $minimum_delay_seconds;
    public $explicit_not_before;
    public $required_external_event;
}
