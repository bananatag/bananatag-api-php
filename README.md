# bananatag-api-php
The Bananatag PHP client is used in conjunction with the Bananatag API which allows you to access your Bananatag data from your own PHP applications. Requires PHP 5.1.2+ and CURL 7.30.0+.

###API Authentication:</h4>

To authenticate a request you must include an authorization header including your auth id and request signiture in base64 encoding. To create the request signature you must first create a data string containing all request parameters. Then using your secret access key provided on sign up, calculate the HMAC of the data string using the HMAC-SHA1 algorithm.

##### Authentication flow

Desired Request: api/v1/tags?start=2013-10-20&end=2013-11-30

**Step 1**: Create Data String  
* Data String = start=2013-10-20&end=2013-11-30

**Step 2**: Generate Signature using HMAC-SHA1 Algorithm
* Using 'hex' encoding
* Using secret access key as the HMAC key
* Using generated data string as HMAC message

**Step 3**: Authorization Header:
* authorization: base64('your AuthID' : 'generated HMAC')  

##### Example Usage
```php

<?php
    require_once('src/BtagAPI.class.php');
    // create BtagAPI class instance
	$btag = new BtagApi('AuthID', 'Access Key');
    // make request for all tags
	$results = $btag->send("tags", []);
    // print list of tags
    echo "Total Tags: " . sizeOf($results) . "<br><hr><br>";
    foreach ($results as $tag) {
        $recipients = [];
        foreach ($tag['recipients'] as $recipient) {
            $recipients[] = $recipient['name'] . " ({$recipient['email']})";
        }
        echo "Tag ID: " . $tag['id'];
        echo "<br>Subject: " . $tag['subject'];
        echo "<br>Recipients: " . implode(", ", $recipients);
        echo "<br>Total Opens: " . $tag['data']['totalOpens'];
        echo "<br>Unique Opens: " . $tag['data']['uniqueOpens'];
        echo "<br>Desktop Opens: " . $tag['data']['desktopOpens'];
        echo "<br>Mobile Opens: " . $tag['data']['mobileOpens'];
        echo "<br>Total Clicks: " . $tag['data']['totalClicks'];
        echo "<br>Unique Clicks: " . $tag['data']['uniqueClicks'];
        echo "<br>Desktop Clicks: " . $tag['data']['desktopClicks'];
        echo "<br>Mobile Clicks: " . $tag['data']['mobileClicks'];
        echo "<br>Date Sent: " . $tag['dateSent'];
        echo "<br><br><hr><br>";
    }
```

###API Endpoints  
To view a list of available endpoints please visit the REST API section of your Bananatag account found under the Resources tab.
