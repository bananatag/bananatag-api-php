Bananatag API - PHP Library
===========================
The Bananatag API PHP Library is used in conjunction Bananatag's REST API (*currently in alpha, available on request only*). The Bananatag REST API allows users access to all data associated with their account and sub-accounts.

### Installation

#### Composer
```json
"require": {
    "bananatag/bananatag-api-php": "dev-master"
}
```
*Not using composer yet, [get composer here.](https://getcomposer.org/)*

### Requires
 * PHP 5.3+ 
 * CURL 7.30.0+.

### Basic Usage

#### Get All Tags
```php

<?php
    use Bananatag\Api;
    
    // Create Api class instance
    $btag = new Api('AuthID', 'Access Key');
	
    // Make request for all tags in date range
	$results = $btag->request("tags", ['start'=>'2015-01-01', 'end'=>'2015-02-01']);
	
    // Print list of tags
    echo "Total Tags: " . count($results['data']) . "<br><hr><br>";
    
    print_r($results['data']);
    
```

#### Pagination
Each time you make a request with the same parameters, the library automatically grabs the next page.
```php
<?php
$btag = new Api('AuthID', 'Access Key');

function getTags(&$btag) {
    $results = $btag->request("tags", []);

    echo $results['paging']['cursors']['next'];

    if ($results['paging']['cursors']['next'] < $results['paging']['cursors']['total']) {
        sleep(1.2);
        getTags($btag);
    }
}

getTags($btag);
```

The recursive example above could be written:
```php
<?php
// Page 1
$results = $btag->request("tags", []);
// Page 2
$results = $btag->request("tags", []);
// Page 3, etc
$results = $btag->request("tags", []);
```
Or you can manually choose a page:
```php
<?php
// Page 1
$results = $btag->request("tags", ['page'=>1]);
// Page 3
$results = $btag->request("tags", ['page'=>3]);
// Page 2
$results = $btag->request("tags", ['page'=>2]);
```

### Request Limit
The API is limited to 1 request per second.

### License
Licensed under the MIT License.