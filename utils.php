<?php
/**
 * Get an element from an array returning a default
 * if the element doesn't exist.
 */
function array_get($array, $key, $default = null)
{
    return isset($array[$key]) ? $array[$key] : $default;
}
/**
 * This function returns an HTTP status corresponding to the result of the
 * current request
 *
 * @param num The HTTP status code
 * @return array containing the HTTP status of request
 */
function set_http_response($num)
{
    $http = array(
        200 => 'HTTP/1.1 200 OK',
        202 => 'HTTP/1.1 202 Accepted',
        400 => 'HTTP/1.1 400 Bad Request',
        500 => 'HTTP/1.1 500 Internal Server Error',
        401 => 'HTTP/1.1 401 Unauthorized Access'
    );
    header($http[$num]);
    return array('CODE' => $num, 'ERROR' => $http[$num]);
}
/**
 * Render the `$res` as JSON with possible debug info appended
 */
function do_result($res)
{
    if (isset($GLOBALS['DEBUG_INFO'])) {
        $res["DEBUG_INFO"] = $GLOBALS['DEBUG_INFO'];
    }
    echo json_encode($res, JSON_PRETTY_PRINT);
}
/**
 * This function formats and echos an exception as an error, given
 * an error object and a response number
 */
function do_error($num = null, $e = null)
{
    if ($num == null) {
        $num = 500;
    }
    $error = set_http_response($num);
    if ($e != null) {
        $error['error_text'] = $e->getMessage();
    }
    if (isset($GLOBALS['DEBUG_INFO'])) {
        $error["DEBUG_INFO"] = $GLOBALS['DEBUG_INFO'];
    }
    echo json_encode($error, JSON_PRETTY_PRINT);
}
/**
 * Turns a string into a bool. Accepts "true", "false", "1", "0".
 */
function as_bool($val)
{
    return filter_var($val, FILTER_VALIDATE_BOOLEAN);
}
/**
 * Parse all data from $_GET and $_POST and pack it in
 * an array that gets returned.
 *
 * Any data sent to $_GET or $_POST that starts with "base64:" will
 * get base64 decoded automatically.
 *
 */
function handle_request()
{
    $result_array = [];
    if (isset($_SERVER['REQUEST_METHOD'])) {
        $result_array['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];
    }
    $tmp_array = [];
    // "post_body" should be gotten directly from the post contents first
    // because a "post_body" URL parameter will override this data
    $tmp_array["post_body"] = file_get_contents('php://input');
    // grab all of the URL parameters
    foreach ($_REQUEST as $key => $value) {
        $tmp_array[$key] = $value;
    }
    // grab the Shibboleth variables
    $shib_array = [];
    foreach (["utorid", "mail", "unscoped-affiliation"] as $var) {
        $shib_array[$var] = array_get($_SERVER, $var);
    }
    // decode anything that starts with base64
    foreach ($tmp_array as $key => $value) {
        try {
            if (substr($value, 0, 7) == "base64:") {
                $result_array[$key] = base64_decode(substr($value, 7));
            } else {
                $result_array[$key] = $value;
            }
        } catch (Exception $e) {
            // on a decode error we do nothing
            // and just leave the array how it was
            $result_array[$key] = $value;
        }
    }
    // tack on the shibboleth variables
    $result_array['auth'] = $shib_array;
    return $result_array;
}
/**
 * Generate a random all-caps override token
 * of lenght `$len`. Letters that are easily confused
 * are omitted entirely. For example, I and 1, and O and 0.
 */
function gen_override_token($len = 6)
{
    $VALID_SYMBOLS = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $ret = "";
    for ($i = 0; $i < $len; $i++) {
        $pos = mt_rand(0, strlen($VALID_SYMBOLS) - 1);
        $ret .= substr($VALID_SYMBOLS, $pos, 1);
    }
    return $ret;
}
/**
 * Pass in a term string (e.g., "20171" and it will normalize
 * to either regular term "YYYY9" or summer term "YYYY5"
 *
 * @term string in the format of YYYYM
 */
function normalize_term($term)
{
    $year = 2017;
    $month = 1;
    if ($term) {
        $year = (int) substr($term, 0, 4);
        $month = (int) substr($term, 4);
    } else {
        date_default_timezone_set('America/Toronto');
        $year = (int) date("Y");
        $month = (int) date("m");
    }
    // regular term overlaps a year, but summer term does not.
    // Parse summer term first.
    if ($month < 8 && $month >= 4) {
        // summer term
        return $year . "5";
    }
    if ($month >= 8) {
        return $year . "9";
    }
    return ($year - 1) . "9";
}

/**
 * Function checks if user_id and utorid match. If not, returns 401 error
 *
 * @throws 401 Unauthorized Access
 */
function verify_user_id($params)
{
    if (!isset($params['user_id'])) {
        $params['user_id'] = $params['auth']['utor_id'];
    }

    if (
        $params['auth']['utorid'] != null &&
        $params['auth']['utorid'] != $params['user_id']
    ) {
        $result = set_http_response(401);
        print json_encode($result, JSON_PRETTY_PRINT);
        exit();
    }
}
