<?php

return [
    'endpoints' => [
        'license-push'        => 'https://self.aplu.io/api/license/verify',
        'subscription-push'     => 'https://self.aplu.io/api/license/subscriber',
        'addon-push'            => 'https://self.aplu.io/api/license/addon-list',
        'addon-licence-push'  => 'https://self.aplu.io/api/license/addon-verify',
        'verify'                => 'https://self.aplu.io/api/verify',
    ],
    'secrets' => [
        'app_domain'   => 'APP_DOMAIN',
        'license_code' => 'LICENSE_CODE',
    ],
];