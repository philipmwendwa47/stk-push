<?php
if (isset($_POST['submit'])) {
    date_default_timezone_set('Africa/Nairobi');

    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Access token credentials
    $consumerKey = 'nk16Y74eSbTaGQgc9WF8j6FigApqOMWr';
    $consumerSecret = '40fD1vRXCq90XFaU';

    // Business credentials
    $BusinessShortCode = '174379';
    $Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

    // User inputs
    $phone = $_POST['phone'];
    $Amount = $_POST['amount'];

    // Log the raw phone input
    echo "Raw phone input: $phone<br>";

    // Normalize the phone number
    $phone = preg_replace('/\s+/', '', $phone); // Remove any spaces
    $phone = preg_replace('/^\+254/', '254', $phone); // Replace +254 with 254
    $phone = preg_replace('/^0/', '254', $phone); // Replace leading 0 with 254
    $phone = preg_replace('/[^0-9]/', '', $phone); // Remove any non-numeric characters

    // Ensure phone number is in the correct length and format
    if (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
        $PartyA = $phone;
    } else {
        die('Invalid phone number format.');
    }

    // Log the formatted phone number
    echo "Formatted phone number: $PartyA<br>";

    // Define other transaction details
    $AccountReference = '2255';
    $TransactionDesc = 'Test Payment';

    // Get the timestamp
    $Timestamp = date('YmdHis');

    // Get the base64 encoded string -> $password
    $Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

    // Header for access token
    $headers = ['Content-Type:application/json; charset=utf8'];

    // M-PESA endpoint urls
    $access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $initiate_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    // Callback URL
    $CallBackURL = 'https://morning-basin-87523.herokuapp.com/callback_url.php';

    // Get access token
    $curl = curl_init($access_token_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($status != 200) {
        die('Error fetching access token: ' . $result);
    }

    $result = json_decode($result);
    $access_token = $result->access_token;
    curl_close($curl);

    // Header for stk push
    $stkheader = ['Content-Type:application/json', 'Authorization:Bearer ' . $access_token];

    // Initiate the transaction
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $initiate_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader); //setting custom header

    $curl_post_data = array(
        'BusinessShortCode' => $BusinessShortCode,
        'Password' => $Password,
        'Timestamp' => $Timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $Amount,
        'PartyA' => $PartyA,
        'PartyB' => $BusinessShortCode,
        'PhoneNumber' => $PartyA,
        'CallBackURL' => $CallBackURL,
        'AccountReference' => $AccountReference,
        'TransactionDesc' => $TransactionDesc
    );

    $data_string = json_encode($curl_post_data);

    // Log the request data
    echo "Request data: $data_string<br>";

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    $curl_response = curl_exec($curl);

    if (curl_errno($curl)) {
        die('Curl error: ' . curl_error($curl));
    }

    $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($response_code != 200) {
        die('Error response from M-PESA: ' . $curl_response);
    }

    curl_close($curl);

    echo 'STK Push initiated successfully. Response: ' . $curl_response;
}
?>
