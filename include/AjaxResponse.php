<?php
// Do no use require_once as this class is included in Company.php.

class AjaxResponse {

    const FORMAT_INT = 1;
    const FORMAT_STRING = 2;
    const FORMAT_HTML = 3;
    const FORMAT_JSON = 4;

    /**
     * NOTE: Value can be of anytype.
     * @param int $status use <= 0 for errors, >0 for success
     * @param $value
     * @param string $message  is shown in sweetalert body
     * @param string $title is used for sweetalert title
     */
    public static function SuccessAndExit_STRING(int $status, $value, string $message, string $title = '') {
        echo json_encode(['status'=>$status,'val'=>$value,'message'=>$message,'title'=>$title,'format'=>self::FORMAT_STRING ]);
        exit();
    }
}
