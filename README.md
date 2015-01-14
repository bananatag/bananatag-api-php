# bananatag-api-php
The Bananatag PHP client is used in conjunction with the Bananatag API which allows you to access your Bananatag data from your own PHP applications. Requires PHP 5.1.2+ and CURL 7.30.0+.

###API Authentication:</h4>

To authenticate a request you must include an authorization header including your auth id and request signiture in base64 encoding. To create the request signature you must first create a data string containing all request parameters. Then using your secret access key provided on sign up, calculate the HMAC of the data string using the HMAC-SHA1 algorithm.

<b>Example:</b>

Desired Request: api/v1/tags?start=2013-10-20&end=2013-11-30

Step 1: Create Data String  
* Data String = start=2013-10-20&end=2013-11-30

Step 2: Generate Signature using HMAC-SHA1 Algorithm
* Using 'hex' encoding
* Using secret access key as the HMAC key
* Using generated data string as HMAC message

Step 3: Authorization Header:
* authorization: base64('your AuthID' : 'generated HMAC')  
  
###API Endpoints  
To view a list of available endpoints please visit the REST API section of your Bananatag account found under the Resources tab.
