<?php
ob_start();
/* 2022-07-07: Disabled this API version */
exit(buildApiResponseAsJson('', '', 0, "Please upgrade your application", 410));
function buildApiResponseAsJson($method = '', $data = '', $success = 1, $message = '', $http_code = 200)
{
    http_response_code($http_code);
    $response = array();
    $response['message'] = $message;
    $response['success'] = $success;
    $response['apiFetchTime'] = time();
    $response['cacheExpiryTime'] = $response['apiFetchTime'] + 3600;
    if ($method) {
        $response['method'] = $method;
    }
    if ($data) {
        $response['data'] = $data;
    }
    //Logger::Log("Returning: ".json_encode($response));
    return json_encode($response);
}