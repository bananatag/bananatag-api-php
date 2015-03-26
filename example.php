<?php
    require_once('src/Api.php');

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
