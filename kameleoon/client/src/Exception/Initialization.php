<?php

namespace Kameleoon\Exception;

/**
 * Thrown by `KameleoonClient::waitInit()` when the SDK could not be initialized: it has no
 * configuration (data file) at all, i.e. the configuration could not be downloaded and no local
 * copy is available. The failure which prevented the SDK from loading its configuration is
 * available via `getPrevious()`.
 */
class Initialization extends KameleoonException
{
}
