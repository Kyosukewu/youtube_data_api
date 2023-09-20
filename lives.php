<?php

    header('Content-Type: application/json; charset=UTF-8');

    $liveTests = [];

    include_once 'common.php';

    $realOptions = ['donations'];

    foreach ($realOptions as $realOption) {
        $options[$realOption] = false;
    }

    if (isset($_GET['part'], $_GET['id'])) {
        $part = $_GET['part'];
        $parts = explode(',', $part, count($realOptions));
        foreach ($parts as $part) {
            if (!in_array($part, $realOptions)) {
                dieWithJsonMessage("Invalid part $part");
            } else {
                $options[$part] = true;
            }
        }

        $ids = $_GET['id'];
        $realIds = str_contains($ids, ',') ? explode(',', $ids, 50) : [$ids];
        if (count($realIds) == 0) {
            dieWithJsonMessage('Invalid id');
        }
        foreach ($realIds as $realId) {
            if ((!isVideoId($realId))) {
                dieWithJsonMessage('Invalid id');
            }
        }

        echo getAPI($realIds);
    } else {
        dieWithJsonMessage("Required parameters not provided");
    }

    function getItem($id)
    {
        global $options;

        $result = getJSONFromHTML("https://www.youtube.com/watch?v=$id");
        $continuation = $result['contents']['twoColumnWatchNextResults']['conversationBar']['liveChatRenderer']['continuations'][0]['reloadContinuationData']['continuation'];

        $opts = [
            "http" => [
                "user_agent" => USER_AGENT,
            ]
        ];
        $html = getJSONFromHTML("https://www.youtube.com/live_chat?continuation=$continuation", $opts, 'window["ytInitialData"]', '');

        $item = [
            'kind' => 'youtube#video',
            'etag' => 'NotImplemented',
            'id' => $id
        ];

        if ($options['donations']) {
            $donations = [];
            $actions = $result['continuationContents']['liveChatContinuation']['actions'];
            foreach ($actions as $action) {
                $donation = $action['addLiveChatTickerItemAction']['item']['liveChatTickerPaidMessageItemRenderer']['showItemEndpoint']['showLiveChatItemEndpoint']['renderer']['liveChatPaidMessageRenderer'];
                if ($donation != null) {
                    array_push($donations, $donation);
                }
            }
            $item['donations'] = $donations;
        }

        return $item;
    }

    function getAPI($ids)
    {
        $items = [];
        foreach ($ids as $id) {
            array_push($items, getItem($id));
        }

        $answer = [
            'kind' => 'youtube#videoListResponse',
            'etag' => 'NotImplemented',
            'items' => $items
        ];

        return json_encode($answer, JSON_PRETTY_PRINT);
    }
