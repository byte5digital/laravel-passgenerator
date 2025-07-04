# ⚠️ Troubleshooting

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

### Pkpass Validator
Is your pass not working? You can use this [PKPass Validator](https://pkpassvalidator.azurewebsites.net/) to check if your pass is valid. It will give you detailed information about any issues with your pass.