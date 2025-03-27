<?php

// Database Configuration
define('DB_HOST', 'localhost'); 
define('DB_USER', 'root');
define('DB_PASS', ''); 
define('DB_NAME', 'eventmgtsyst'); 


// Function to get the database connection
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
// The name and address which should be used for the sender details.
// The name can be anything you want, the address should be something in your own domain. It does not need to exist as a mailbox.
define('CONTACTFORM_FROM_ADDRESS', 'judith.tikou@gmail.com');
define('CONTACTFORM_FROM_NAME', 'jeason');

// The name and address to which the contact message should be sent.
// These details should NOT be the same as the sender details.
define('CONTACTFORM_TO_ADDRESS', 'jeasonangel0@gmail.com');
define('CONTACTFORM_TO_NAME', 'jeason angel');

// The details of your SMTP service, e.g. Gmail.
define('CONTACTFORM_SMTP_HOSTNAME', 'smtp.gmail.com');
define('CONTACTFORM_SMTP_USERNAME', 'jeasonangel0@gmail.com');
define('CONTACTFORM_SMTP_PASSWORD', 'wjjb ibqd gxlw nveo');

// The reCAPTCHA credentials for your site. You can get these at https://www.google.com/recaptcha/admin
define('CONTACTFORM_RECAPTCHA_SITE_KEY', '6LcsAvwqAAAAADFjAeGebya4WB9Sv7fn-8nKVBB_');
define('CONTACTFORM_RECAPTCHA_SECRET_KEY', '6LcsAvwqAAAAAP0jwagLn262T4wLEdKj81MEuGti');



// Which SMTP port and encryption type to use. The default is probably fine for most use cases.
define('CONTACTFORM_SMTP_PORT', 587);
define('CONTACTFORM_SMTP_ENCRYPTION', ' PHPMailer::ENCRYPTION_STARTTLS');

// Character encoding settings. The default is probably fine for most use cases.
define('CONTACTFORM_MAIL_CHARSET', 'iso-8859-1'); // Can be: us-ascii, iso-8859-1, utf-8. Default: iso-8859-1.
define('CONTACTFORM_MAIL_ENCODING', '8bit'); // Can be: 7bit, 8bit, base64, binary, quoted-printable. Default: 8bit.

// The language used for error message and the like.
// Supports 2 letter language codes, e.g. en, fr, es, cn. Default: en.
define('CONTACTFORM_LANGUAGE', 'en');