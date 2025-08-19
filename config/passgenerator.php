<?php

return [
    'config_disk' => env('PASSGENERATOR_CONFIG_DISK'), // The disk to use for storing the pass configuration files and certificates

    'certificate_store_path' => env('CERTIFICATE_PATH', ''), // The path to the certificate store (a valid  PKCS#12 file)
    'certificate_store_password' => env('CERTIFICATE_PASS', ''), // The password to unlock the certificate store
    'wwdr_certificate_path' => env('WWDR_CERTIFICATE', ''), // Get from here https://www.apple.com/certificateauthority/ and export to PEM

    'storage_disk' => env('PASSGENERATOR_STORAGE_DISK', 'local'), // The disk to use for storing the pass files
    'storage_path' => env('PASSGENERATOR_STORAGE_PATH', 'passgenerator/certs'), // The path to store the pass files on the disk

    'pass_type_identifier' => env('PASS_TYPE_IDENTIFIER', ''),
    'organization_name' => env('ORGANIZATION_NAME', ''),
    'team_identifier' => env('TEAM_IDENTIFIER', ''),

];
