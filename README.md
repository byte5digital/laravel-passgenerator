<img src="https://byte5-relaunch-staging.azurewebsites.net/media/yonjlhuc/byte5-package-banner-passgenerator.png" alt="Laravel Passgenerator Banner"/>

# Passgenerator
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/byte5digital/laravel-passgenerator/ci.yaml)
![Packagist Version](https://img.shields.io/packagist/v/byte5/laravel-passgenerator)
![GitHub License](https://img.shields.io/github/license/byte5digital/laravel-passgenerator)

Passgenerator is a Laravel package that allows you to easily create passes compatible with Apple Wallet (former Passbook).

### Documentation
You can find the full documentation [here](https://byte5digital.github.io/laravel-passgenerator).

# 👉 Table of Contents 👈
* [👮 Requirements](#-requirements)
* [💾 Installation](#-installation)
* [🍎 Apple docs](#-apple-docs)
* [📝 Configuration](#-configuration)
* [🚀 Usage](#-usage)
* [❓ Troubleshooting](#-troubleshooting)
* [❓ About us](#-about-us)

## 👮 Requirements

* Laravel 7 or higher

### PHP extensions
* ext-json
* ext-zip
* ext-openssl


## 💾 Installation

The best and easiest way to install the package is using the [Composer](https://getcomposer.org/) package manager. To do so, run this command in your project root:

```sh
composer require byte5/passgenerator
```

Then, add the `Byte5\PassGeneratorServiceProvider` provider to the providers array in `config/app.php`:

```php
'providers' => [
// ...
    Byte5\PassGeneratorServiceProvider::class,
],
```

That's it!

## 🍎 Apple docs
From now on, some stuff is much better explained on the Apple docs, so when in doubt just check (if you haven't done so) the following documents:
* [Wallet Portal](https://developer.apple.com/wallet/)
* [Wallet Developer Guide](https://developer.apple.com/library/ios/documentation/UserExperience/Conceptual/PassKit_PG/index.html#//apple_ref/doc/uid/TP40012195)
* [Crypto Signatures](https://developer.apple.com/library/ios/documentation/UserExperience/Conceptual/PassKit_PG/Creating.html#//apple_ref/doc/uid/TP40012195-CH4-SW55)
* [PassKit Package Format Reference](https://developer.apple.com/library/ios/documentation/UserExperience/Reference/PassKit_Bundle/Chapters/Introduction.html#//apple_ref/doc/uid/TP40012026)

## 📝 Configuration
To start using the package some Apple files are needed, as well as some action in order to convert them to more friendly formats:

1. Go to the [Apple Developer page ➵ Identifiers ➵ Pass Type IDs](https://developer.apple.com/account/ios/identifiers/passTypeId/passTypeIdList.action).

2. Next, you need to create a pass type ID. This is similar to the bundle ID for apps. It will uniquely identify a specific kind of pass. It should be of the form of a reverse-domain name style string (i.e., pass.com.example.appname).

3. After creating the pass type ID, click on `Edit` and follow the instructions to create a new Certificate.

4. Once the process is finished, the pass certificate can be downloaded. That's not it though, the certificate is downloaded as `.cer` file, which need to be converted to `.p12` in order to work. If you are using a Mac you can import it into _Keychain Access_ and export it from there. Make sure to *remember the password* you have given to the exported file since you'll have to use it later. You can also use other tools to convert the certificate but be sure it includes the private key on the exported PKCS12 file.

5. If you have made iOS development, you probably have already the _Apple Worldwide Developer Relations Intermediate Certificate_ in your Mac’s keychain. If not, it can be downloaded from the [Apple Website](https://www.apple.com/certificateauthority/) (on `.cer` format). This one needs to be exported as `.pem`, you can also do so from _Keychain Access_ (or whatever tool you use to manage certificates on your OS).


Once all this tedious process has been done, everything is almost ready to start using the package. The easiest now is to add to the following keys to your `.env` file:

* CERTIFICATE_PATH ➪ The path to the `.p12` pass certificate.
* CERTIFICATE_PASS ➪ The password set to unlock the certificate when it was exported.
* WWDR_CERTIFICATE ➪ The path to the _Apple Worldwide Developer Relations Intermediate Certificate_ on `.pem` format.
* PASSGENERATOR_STORAGE_DISK ➪ The name of the disk where the passes will be stored. This should match a disk defined in your `config/filesystems.php` file. By default, it is set to `local`.
* PASSGENERATOR_STORAGE_PATH ➪ The path where the passes will be stored. By default, it is set to `passgenerator/certs`.

In case there is a reason the config file must be modified (conflicting env keys, dynamic certificates required...), it can be published with the following command:

```sh
// file will be at config/passgenerator.php
php artisan vendor:publish --provider="Byte5\PassGeneratorServiceProvider"
```

#### Below is a reasonable default for your .env file:
```dotenv
CERTIFICATE_PATH="passgenerator/certs/certificate.p12"
CERTIFICATE_PASS="YOUR-CERTIFICATE-PASSWORD"
WWDR_CERTIFICATE="passgenerator/certs/AppleWWDRCA.pem"
PASSGENERATOR_STORAGE_DISK="private"
PASSGENERATOR_STORAGE_PATH="passgenerator/passes"
```

This will ensure the following file structure:
```
storage/
├─ app/
│  ├─ private/
│  │  ├─ passgenerator/
│  │  │  ├─ certs/
│  │  │  │  ├─ AppleWWDRCA.pem
│  │  │  │  ├─ certificate.p12
│  │  │  ├─ passes/
│  │  │  │  ├─ your_generated_pass.pkpass
```

## 🚀 Usage
To create a pass for the first time, you have to first create the pass definition, either as a JSON file or as an array. It is *really* recommended to have already read the [Apple docs](https://developer.apple.com/library/ios/documentation/UserExperience/Conceptual/PassKit_PG/YourFirst.html#//apple_ref/doc/uid/TP40012195-CH2-SW1) as well as the [PassKit Package Format Reference](https://developer.apple.com/library/ios/documentation/UserExperience/Reference/PassKit_Bundle/Chapters/Introduction.html#//apple_ref/doc/uid/TP40012026).

```php

use Byte5\PassGenerator;

//...

$pass_identifier = 'somekindofid';  // This, if set, it would allow for retrieval later on of the created Pass

$pass = new PassGenerator($pass_identifier);

$pass_definition = [
    "description"       => "description",
    "formatVersion"     => 1,
    "organizationName"  => "organization",
    "passTypeIdentifier"=> "pass.com.example.appname",
    "serialNumber"      => "123456",
    "teamIdentifier"    => "teamid",
    "foregroundColor"   => "rgb(99, 99, 99)",
    "backgroundColor"   => "rgb(212, 212, 212)",
    "barcode" => [
        "message"   => "encodedmessageonQR",
        "format"    => "PKBarcodeFormatQR",
        "altText"   => "altextfortheQR",
        "messageEncoding"=> "utf-8",
    ],
    "boardingPass" => [
        "headerFields" => [
            [
                "key" => "destinationDate",
                "label" => "Trip to: BCN-SANTS",
                "value" => "15/09/2015"
            ]
        ],
        "primaryFields" => [
            [
                "key" => "boardingTime",
                "label" => "MURCIA",
                "value" => "13:54",
                "changeMessage" => "Boarding time has changed to %@"
            ],
            [
                "key" => "destination",
                "label" => "BCN-SANTS",
                "value" => "21:09"
            ]

        ],
        "secondaryFields" => [
            [
                "key" => "passenger",
                "label" => "Passenger",
                "value" => "J.DOE"
            ],
            [
                "key" => "bookingref",
                "label" => "Booking Reference",
                "value" => "4ZK6FG"
            ]
        ],
        "auxiliaryFields" => [
            [
                "key" => "train",
                "label" => "Train TALGO",
                "value" => "00264"
            ],
            [
                "key" => "car",
                "label" => "Car",
                "value" => "009"
            ],
            [
                "key" => "seat",
                "label" => "Seat",
                "value" => "04A"
            ],
            [
                "key" => "classfront",
                "label" => "Class",
                "value" => "Tourist"
            ]
        ],
        "backFields" => [
            [
                "key" => "ticketNumber",
                "label" => "Ticket Number",
                "value" => "7612800569875"
            ], [
                "key" => "passenger-name",
                "label" => "Passenger",
                "value" => "John Doe"
            ], [
                "key" => "classback",
                "label" => "Class",
                "value" => "Tourist"
            ]
        ],
        "locations" => [
            [
                "latitude" => 37.97479,
                "longitude" => -1.131522,
                "relevantText" => "Departure station"
            ]
        ],
        "transitType" => "PKTransitTypeTrain"
    ],
];

$pass->setPassDefinition($pass_definition);

// Definitions can also be set from a JSON string
// $pass->setPassDefinition(file_get_contents('/path/to/pass.json));

// Add assets to the PKPass package
$pass->addAsset(base_path('resources/assets/wallet/background.png'));
$pass->addAsset(base_path('resources/assets/wallet/thumbnail.png'));
$pass->addAsset(base_path('resources/assets/wallet/icon.png'));
$pass->addAsset(base_path('resources/assets/wallet/logo.png'));

$pkpass = $pass->create();

```
Now, a valid ticket is already in place. Apple recommends a MIME type to serve it to its devices so something like the following should do:

```php
return new Response($pkpass, 200, [
    'Content-Transfer-Encoding' => 'binary',
    'Content-Description' => 'File Transfer',
    'Content-Disposition' => 'attachment; filename="pass.pkpass"',
    'Content-length' => strlen($pkpass),
    'Content-Type' => PassGenerator::getPassMimeType(),
    'Pragma' => 'no-cache',
]);
```

Later on, if your users need to download the pass again, you don't need to create it again (wasting all those CPU cycles on crypto stuff), you can just do something like:

```php
// If the pass for that ID does not exist, you can then proceed to generate it as done above.
$pkpass = PassGenerator::getPass($pass_identifier);
if (!$pkpass) {
    $pkpass = $this->createWalletPass();
}
// ...
```

It is also possible to retrieve the actual path to a pass on your filesystem. By default, _Passgenerator_  will copy your default filesystem config (usually rooted on `storage_path('app')` but you can always do `getPassFilePath($pass_identifier)` and retrieve the real path (in case it exists).

### Definitions
It is also possible to programatically create/modify a pass using the definitions objects. Eg.-

```
$coupon = Byte5\Definitions\Coupon();
$coupon->setDescription('Coupon description');
$coupon->setSerialNumber('123456');

$coupon->setUserInfo([
    'email' => 'user@domain.com',
]);
$coupon->setExpirationDate(Carbon::now()->addMonths(6));

$location = new Location();
$location->setLatitude(40.4378698);
$location->setLongitude(-3.819619);
$coupon->addLocation($location);

$coupon->setMaxDistance(50);
$coupon->setRelevantDate(Carbon::now()->addDays(10));

$coupon->addAuxiliaryField(new Field('key', 'value'));

$coupon->addBackField(new Number('price', 13, [
    'currencyCode' => 'EUR',
    'numberStyle' => Number::STYLE_DECIMAL
]));

$coupon->addPrimaryField(new Date('created_at', Carbon::now(), [
    'dateStyle' => Date::STYLE_FULL,
]));

$barcode = new Barcode('7898466321', Barcode::FORMAT_CODE128);
$coupon->addBarcode($barcode);

$passgenerator->setPassDefinition($coupon);
```

## ❓ Troubleshooting
### **error:0308010C:digital envelope routines::unsupported**
If you run into this error, you are probably using OpenSSL 3.0 and trying to work with a legacy certificate.
While some people recommend to enable the legacy option for OpenSSL 3.0, this doesn't solve the core problem.

Instead, you should convert your certificate to a more modern format. You can do this by running the following commands:

```sh
#unpack the key: 
openssl pkcs12 -in PassType.p12 -nodes -out key_decrypted.tmp

#pack the key using a more moden algorithm:
openssl pkcs12 -export -in key_decrypted.tmp -out new.p12 -certpbe AES-256-CBC -keypbe AES-256-CBC
```

## ❓ About Us

<img src="https://raw.githubusercontent.com/byte5digital/.github/main/profile/assets/byte5.svg" alt="byte5 Logo" width="200" />

We’re a development company based in Frankfurt, Germany — **remote-friendly, open-minded, and tech-driven**. Our team brings deep expertise in Laravel, Umbraco, and decentralized tech like IOTA. We collaborate with clients who care about clean code, scalable solutions, and long-term maintainability.

We contribute to open source, run [Laravel DACH Meetups](https://www.meetup.com/laravel-meetup-germany/), and support developer communities across the DACH region.

If you love building smart solutions with real impact — we should talk.