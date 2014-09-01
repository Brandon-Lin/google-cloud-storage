<?php
/*
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
session_start();

/************************************************
  Make an API request authenticated with a service
  account.
 ************************************************/
set_include_path("src/" . PATH_SEPARATOR . get_include_path());
require_once 'src/Google/Client.php';
require_once 'src/Google/Service/Storage.php';

/************************************************
  ATTENTION: Fill in these values! You can get
  them by creating a new Service Account in the
  API console. Be sure to store the key file
  somewhere you can get to it - though in real
  operations you'd want to make sure it wasn't
  accessible from the webserver!
  The name is the email address value provided
  as part of the service account (not your
  address!)
 ************************************************/

/*-- begin of settings --*/
$bucket_name = 'Your Bucket Name';
$local_file_path = "File path in local"; // i.e. /tmp/some_picture.jpg path of upload file in local machine
$file_name_in_gcs = "File path in GCS";  // i.e  pictures/new_picture.jpg. (file name appeared in google cloud storage) 
$service_account_name = 'Email Address'; // i.e  4902what6393-n30boe8l2db8tqocgvcl1m60a944jka7@developer.gserviceaccount.com (Email Address)
$key_file_location = 'OAuth2 Key.12 file path in local'; // i.e /var/www/your-173ec6d96fae.p12   (Service Account of OAuth2)
$app_name = "App Name";                  //(Not a crucial setting, you can leave it unchanged.)
$storage_permission = 'https://www.googleapis.com/auth/devstorage.read_write'; // permission to read and write (Do not modify)

/*-- end of settings --*/

// check for invalid settings (Not important)
if (!strlen($service_account_name)
    || !strlen($key_file_location)) {
  echo missingServiceAccountDetailsWarning();
}
$client = new Google_Client();
$client->setApplicationName($app_name);

/************************************************
  If we have an access token, we can carry on.
  Otherwise, we'll get one with the help of an
  assertion credential. In other examples the list
  of scopes was managed by the Client, but here
  we have to list them manually. We also supply
  the service account
 ************************************************/
if (isset($_SESSION['service_token'])) {
  $client->setAccessToken($_SESSION['service_token']);
}

// get OAuth2 P12 key (System Account)
$key = file_get_contents($key_file_location);

// set read/write permission 
$cred = new Google_Auth_AssertionCredentials(
    $service_account_name,
    array($storage_permission),
    $key
);

// set OAuth2 credential for client side (i.e. your server or Google Compute Engine)
$client->setAssertionCredentials($cred);
if($client->getAuth()->isAccessTokenExpired()) {
  $client->getAuth()->refreshTokenWithAssertion($cred);
}

/************************************************
  We're just going to make the same call as in the
  simple query as an example.
 ************************************************/
$service = new Google_Service_Storage($client);

// set file name appeared in cloud storage (GCS)
$obj = new Google_Service_Storage_StorageObject();
$obj->setName($file_name_in_gcs);

// upload local file (file name: $local_file_path) 
$results = $service->objects->insert(
    $bucket_name,
    $obj,
    ['name' => $file_name_in_gcs, 'data' => file_get_contents($local_file_path), 'uploadType' => 'media']
);

// print result
echo "<h3>Results Of Call:</h3>";
echo "<pre>";
var_dump($results);
echo "</pre>";

?>
