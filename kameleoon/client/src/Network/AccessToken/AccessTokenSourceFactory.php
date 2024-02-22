<?php

namespace Kameleoon\Network\AccessToken;

use Kameleoon\Network\NetworkManager;

interface AccessTokenSourceFactory
{
    public function create(NetworkManager $networkManager): AccessTokenSource;
}
