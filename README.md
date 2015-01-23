# Bananatag API - PHP Library
The Bananatag API PHP Library is used in conjunction Bananatag's REST API (*currently in alpha, available on request only*). The Bananatag REST API allows users access to all data associated with their account and sub-accounts. 

#####Requires
 * PHP 5.1.2+ 
 * CURL 7.30.0+.

#### Example Usage
```php

<?php
    require_once('src/BtagAPI.class.php');
    // create BtagAPI class instance
	$btag = new BtagApi('AuthID', 'Access Key');
    // make request for all tags
	$results = $btag->send("tags", ['start':'2015-01-01', 'end':'2015-02-01']);
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
### Request Limit
The API is limited to 1 request per second.

###API Endpoints  
To view a list of available endpoints please visit the REST API section of your Bananatag account found under the Resources tab.
