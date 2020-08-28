# SSL Client Certificates Example

This example shows how a client can be authenticated using SSL client certificates.

## Setup the Certificate Authority

### Create the root key and certificate

```
openssl genrsa -des3 -out ssl/private/root.key.pem -passout env:SSL_PASSWORD 2048
openssl req -new \
    -key ssl/private/root.key.pem \
    -out ssl/csr/root.csr.pem \
    -passin env:SSL_PASSWORD \
    -subj "/C=GB/ST=England/L=Newcastle upon Tyne/O=iamproperty/OU=Software/CN=iamproperty Root CA"
openssl x509 -req -sha256 -CAcreateserial -extfile ssl/v3.ext \
    -signkey ssl/private/root.key.pem \
    -in ssl/csr/root.csr.pem \
    -out ssl/certs/root.cert.pem \
    -passin env:SSL_PASSWORD
```

### Create the intermediate key and certificate

```
openssl req -newkey rsa:2048 -new -sha256 \
        -out ssl/csr/intermediate.csr.pem \
        -keyout ssl/private/intermediate.key.pem \
        -passout env:SSL_PASSWORD \
        -subj "/C=GB/ST=England/L=Newcastle upon Tyne/O=iamproperty/OU=Software/CN=iamproperty Intermediate CA"
```

Sign the CSR with the Root key

```
openssl x509 -req -sha256 -CAcreateserial -extfile ssl/v3.ext \
    -CA ssl/certs/root.cert.pem \
    -CAkey ssl/private/root.key.pem \
    -in ssl/csr/intermediate.csr.pem \
    -out ssl/certs/intermediate.cert.pem \
    -passin env:SSL_PASSWORD
```

### Create the certificate chain file

```
cat ssl/certs/intermediate.cert.pem ssl/certs/root.cert.pem > ssl/certs/ca-chain.cert.pem
```

## Create a Certificate for the Server 

```
openssl req -newkey rsa:2048 -new -sha256 \
        -out ssl/csr/localhost.csr.pem \
        -keyout ssl/private/localhost.key.pem \
        -passout env:SSL_PASSWORD \
        -subj "/C=GB/ST=England/L=Newcastle upon Tyne/O=iamproperty/OU=Software/CN=localhost"
openssl x509 -req -sha256 -CAcreateserial \
    -extfile <(printf "subjectAltName=DNS:localhost") \
    -CA ssl/certs/intermediate.cert.pem \
    -CAkey ssl/private/intermediate.key.pem \
    -in ssl/csr/localhost.csr.pem \
    -out ssl/certs/localhost.cert.pem \
    -passin env:SSL_PASSWORD
```

## Create a Client Certificate 

```
openssl req -newkey rsa:2048 -new -sha256 \
        -out ssl/csr/$USER.csr.pem \
        -keyout ssl/private/$USER.key.pem \
        -passout env:SSL_PASSWORD \
        -subj "/C=GB/ST=England/L=Newcastle upon Tyne/O=iamproperty/OU=Software/CN=$USER <$USER@example.com>/emailAddress=$USER@example.com"
openssl x509 -req -sha256 \
    -CA ssl/certs/intermediate.cert.pem \
    -CAkey ssl/private/intermediate.key.pem \
    -in ssl/csr/$USER.csr.pem \
    -out ssl/certs/$USER.cert.pem \
    -passin env:SSL_PASSWORD
```

## Install the Client Certificate

Create a PKCS12 formatted certificate.

```
openssl pkcs12 -export \
    -in ssl/certs/$USER.cert.pem \
    -inkey ssl/private/$USER.key.pem \
    -out ssl/certs/$USER.pfx \
    -passin env:SSL_PASSWORD \
    -passout env:SSL_PASSWORD
```

### Chrome

*These instructions are for MacOS only.*

Chrome uses the operating system to provide certificates so you will need to add them to your keychain.

Use the open command to import the certificates into *Keychain Access*. This will give you the option to add
the certificate. After you have added it you should alter its trust settings.
Set `SSL` and `X.509 Basic Policy` to `Always Trust`. Close the certificate window and you should be prompted 
for your password to change the trust settings.

```
open ssl/certs/root.cert.pem
```

After this you should be able to import the intermediate and client certificates and they should be trusted.

```
open ssl/certs/intermediate.cert.pem
open ssl/certs/<name>.pfx
```

### Firefox

Under [about:preferences#privacy](about:preferences#privacy) there is a *View Certificates...* option.
This will open a dialog where you can upload the certificate. 

### Postman

In the settings there is a *Certificates* option.

In the **CA Certificates** section add the `ca-chain.cert.pem` file so Postman can verify the server 
certificate.

In the **Client Certificates** section clicking *Add Certificate* will open a dialog where you
can select files. Depending on the version of Postman you can either add a certificate and key pair or 
the `.pfx` file.

## Trying the Demo

Start the demo using `docker-compose up`. Visit [https://localhost](https://localhost) in a browser.

You might have to trust the server certificate. The browser should ask you which certificate you would like 
to use.

If everything is working you should then be shown a page of PHP SSL `$_SERVER` variables.
