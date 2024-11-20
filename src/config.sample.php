<?php
// Copy this file to config.php and fill it with your server configuration
define("OIDC_ID_PROVIDER", "https://your_provider/application/o/you_app_name/");
define("OIDC_RESPONSE_TYPE", "code");
define("OIDC_CLIENT_ID", "The app ID");
define("OIDC_CLIENT_SECRET", "The app secret");
define("DB_HOST","mariadb");
define("DB_PASSWORD","password");
define("DB_NAME","tour");
define("DB_USER","lmddc");
define("REDIRECT_PROTOCOL","https"); //Set it to http if you use a SSL proxy

define("SKY_WIDTH", 16384);
define("SKY_HEIGHT", 8192);
define("SKY_QUALITY", 95);
define("SKY_LENGTH", 25000000); // reject file if its size > 25MB

define("SKY_PRELOAD_WIDTH", 2048);
define("SKY_PRELOAD_HEIGHT", 1024);
define("SKY_PRELOAD_LENGTH", 1000000); // reject file if its size > 1MB

define("SKY_THUMB_WIDTH", 400);
define("SKY_THUMB_HEIGHT", 250);
define("SKY_THUMB_QUALITY", 80);
define("SKY_THUMB_LENGTH", 200000); // reject file > 200ko

define("TOUR_THUMB_WIDTH", 400);
define("TOUR_THUMB_HEIGHT", 250);
define("TOUR_THUMB_QUALITY", 90);
define("TOUR_THUMB_LENGTH", 100000); // reject file > 100ko

define("POI_IMAGE_WIDTH", 2000);
define("POI_IMAGE_HEIGHT", 2000);
define("POI_IMAGE_QUALITY", 90);
define("POI_IMAGE_LENGTH", 2000000); // reject file if its size > 2MB

define("IMAGICK_LIMIT_WIDTH", 20000);
define("IMAGICK_LIMIT_HEIGHT", 10000);
// User agent used for external queries (thumbnail generator)
define("USER_AGENT", "Virtual Tour App (https://example.com)");
define("DEV", false);
define("LICENSES", [
  "UNLICENSED",
  "CC-BY-4.0",
  "CC-BY-NC-4.0",
  "CC-BY-NC-ND-4.0",
  "CC-BY-NC-SA-4.0",
  "CC-BY-ND-4.0",
  "CC-BY-SA-4.0",
  "CC0-1.0"
]);
//icon name is max 10chars
define("POI_ICONS", [
  "idea",
  "image",
  "place",
  "question",
  "text"
]);
define("DEMO_TOUR_ID", 0); // Id of the demo tour that will be copied in user's collection
define("DEMO_CREATION_DATE", "2023-09-18 00:00:00"); // A trick to recognize a demo in user's collection
