<?php

include("config.php");
include("check_login.php");
//include("functions.php");

require("vendor/PHPMailer-6.0.7/src/PHPMailer.php");
require("vendor/PHPMailer-6.0.7/src/SMTP.php");

$mpdf_path = (getenv('MPDF_ROOT')) ? getenv('MPDF_ROOT') : __DIR__;
require_once $mpdf_path . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$todays_date = date('Y-m-d');

if(isset($_POST['edit_general_settings'])){

    $config_start_page = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_start_page']));
    $config_account_balance_threshold = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_account_balance_threshold']));

    mysqli_query($mysqli,"UPDATE settings SET config_start_page = '$config_start_page', config_account_balance_threshold = '$config_account_balance_threshold'");

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_POST['edit_company_settings'])){

    $config_company_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_company_name']));
    $config_company_address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_company_address']));
    $config_company_city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_company_city']));
    $config_company_state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_company_state']));
    $config_company_zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_company_zip']));
    $config_company_phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_company_phone']));
    $config_company_site = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_company_site']));
   


    mysqli_query($mysqli,"UPDATE settings SET config_company_name = '$config_company_name', config_company_address = '$config_company_address', config_company_city = '$config_company_city', config_company_state = '$config_company_state', config_company_zip = '$config_company_zip', config_company_phone = '$config_company_phone', config_company_site = '$config_company_site'");

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_POST['edit_mail_settings'])){

    $config_smtp_host = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_smtp_host']));
    $config_smtp_port = intval($_POST['config_smtp_port']);
    $config_smtp_username = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_smtp_username']));
    $config_smtp_password = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_smtp_password']));

    mysqli_query($mysqli,"UPDATE settings SET config_smtp_host = '$config_smtp_host', config_smtp_port = $config_smtp_port, config_smtp_username = '$config_smtp_username', config_smtp_password = '$config_smtp_password'");

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_POST['edit_invoice_settings'])){

    $config_next_invoice_number = intval($_POST['config_next_invoice_number']);
    $config_mail_from_email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_mail_from_email']));
    $config_mail_from_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_mail_from_name']));
    $config_invoice_footer = strip_tags(mysqli_real_escape_string($mysqli,$_POST['config_invoice_footer']));

    mysqli_query($mysqli,"UPDATE settings SET config_next_invoice_number = '$config_next_invoice_number', config_mail_from_email = '$config_mail_from_email', config_mail_from_name = '$config_mail_from_name', config_invoice_footer = '$config_invoice_footer'");

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['download_database'])){

    // Get All Table Names From the Database
    $tables = array();
    $sql = "SHOW TABLES";
    $result = mysqli_query($mysqli, $sql);

    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }

    $sqlScript = "";
    foreach ($tables as $table) {
        
        // Prepare SQLscript for creating table structure
        $query = "SHOW CREATE TABLE $table";
        $result = mysqli_query($mysqli, $query);
        $row = mysqli_fetch_row($result);
        
        $sqlScript .= "\n\n" . $row[1] . ";\n\n";
        
        
        $query = "SELECT * FROM $table";
        $result = mysqli_query($mysqli, $query);
        
        $columnCount = mysqli_num_fields($result);
        
        // Prepare SQLscript for dumping data for each table
        for ($i = 0; $i < $columnCount; $i ++) {
            while ($row = mysqli_fetch_row($result)) {
                $sqlScript .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < $columnCount; $j ++) {
                    $row[$j] = $row[$j];
                    
                    if (isset($row[$j])) {
                        $sqlScript .= '"' . $row[$j] . '"';
                    } else {
                        $sqlScript .= '""';
                    }
                    if ($j < ($columnCount - 1)) {
                        $sqlScript .= ',';
                    }
                }
                $sqlScript .= ");\n";
            }
        }
        
        $sqlScript .= "\n"; 
    }

    if(!empty($sqlScript))
    {
        // Save the SQL script to a backup file
        $backup_file_name = date('Y-m-d') . '_' . $config_company_name . '_backup.sql';
        $fileHandler = fopen($backup_file_name, 'w+');
        $number_of_lines = fwrite($fileHandler, $sqlScript);
        fclose($fileHandler); 

        // Download the SQL backup file to the browser
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($backup_file_name));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($backup_file_name));
        ob_clean();
        flush();
        readfile($backup_file_name);
        exec('rm ' . $backup_file_name); 
    }

}

if(isset($_POST['add_user'])){

    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));
    $password = md5(mysqli_real_escape_string($mysqli,$_POST['password']));

    mysqli_query($mysqli,"INSERT INTO users SET name = '$name', email = '$email', password = '$password'");

    $user_id = mysqli_insert_id($mysqli);

    $check = getimagesize($_FILES["avatar"]["tmp_name"]);
    if($check !== false) {
        $avatar_path = "uploads/user_avatars/";
        $avatar_path = $avatar_path . $user_id . '_' . time() . '_' . basename( $_FILES['avatar']['name']);
        move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path);
    }else{
        $avatar_path = "img/default_user_avatar.png";
    }

    mysqli_query($mysqli,"UPDATE users SET avatar = '$avatar_path' WHERE user_id = $user_id");

    $_SESSION['alert_message'] = "User added";
    
    header("Location: users.php");

}

if(isset($_POST['edit_user'])){

    $user_id = intval($_POST['user_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));
    $current_password_hash = mysqli_real_escape_string($mysqli,$_POST['current_password_hash']);
    $password = mysqli_real_escape_string($mysqli,$_POST['password']);
    if($current_password_hash == $password){
        $password = $current_password_hash;
    }else{
        $password = md5($password);
    }
    $avatar_path = $_POST['current_avatar_path'];
    $check = getimagesize($_FILES["avatar"]["tmp_name"]);
    if($check !== false) {
        if($avatar_path != "img/default_user_avatar.png"){
            unlink($avatar_path);
        }
        $avatar_path = "uploads/user_avatars/";
        $avatar_path =  $avatar_path . $user_id . '_' . time() . '_' . basename( $_FILES['avatar']['name']);
        move_uploaded_file($_FILES['avatar']['tmp_name'], "$avatar_path");
    }
    
    mysqli_query($mysqli,"UPDATE users SET name = '$name', email = '$email', password = '$password', avatar = '$avatar_path' WHERE user_id = $user_id");

    $_SESSION['alert_message'] = "User updated";
    
    header("Location: users.php");

}

if(isset($_POST['add_client'])){

    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['address']));
    $city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['city']));
    $state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['state']));
    $zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['zip']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));
    $website = strip_tags(mysqli_real_escape_string($mysqli,$_POST['website']));
    $net_terms = intval($_POST['net_terms']);

    mysqli_query($mysqli,"INSERT INTO clients SET client_name = '$name', client_address = '$address', client_city = '$city', client_state = '$state', client_zip = '$zip', client_phone = '$phone', client_email = '$email', client_website = '$website', client_net_terms = $net_terms, client_created_at = UNIX_TIMESTAMP()");

    $client_id = mysqli_insert_id($mysqli);

    mysqli_query($mysqli,"INSERT INTO client_contacts SET client_contact_name = '$name', client_contact_title = 'Main Contact', client_contact_phone = '$phone', client_contact_email = '$email', client_id = $client_id");

    mysqli_query($mysqli,"INSERT INTO client_locations SET client_location_name = 'Main', client_location_address = '$address', client_location_city = '$city', client_location_state = '$state', client_location_zip = '$zip', client_location_phone = '$phone', client_id = $client_id");

    if(!empty($_POST['website'])) {
        mysqli_query($mysqli,"INSERT INTO client_domains SET client_domain_name = '$website', client_id = $client_id");
    }

    mkdir("uploads/client_files/$client_id");

    $_SESSION['alert_message'] = "Client added";
    
    header("Location: clients.php");

}

if(isset($_POST['edit_client'])){

    $client_id = intval($_POST['client_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['address']));
    $city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['city']));
    $state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['state']));
    $zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['zip']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));
    $website = strip_tags(mysqli_real_escape_string($mysqli,$_POST['website']));

    mysqli_query($mysqli,"UPDATE clients SET client_name = '$name', client_address = '$address', client_city = '$city', client_state = '$state', client_zip = '$zip', client_phone = '$phone', client_email = '$email', client_website = '$website', client_updated_at = UNIX_TIMESTAMP() WHERE client_id = $client_id");

    $_SESSION['alert_message'] = "Client updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_client'])){
    $client_id = intval($_GET['delete_client']);

    mysqli_query($mysqli,"DELETE FROM clients WHERE client_id = $client_id");

    $_SESSION['alert_message'] = "Client deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_calendar_event'])){

    $calendar_id = intval($_POST['calendar']);
    $title = strip_tags(mysqli_real_escape_string($mysqli,$_POST['title']));
    $start = strip_tags(mysqli_real_escape_string($mysqli,$_POST['start']));
    $end = strip_tags(mysqli_real_escape_string($mysqli,$_POST['end']));

    mysqli_query($mysqli,"INSERT INTO calendar_events SET calendar_event_title = '$title', calendar_event_start = '$start', calendar_event_end = '$end', calendar_id = $calendar_id");

    $_SESSION['alert_message'] = "Event added to the calendar";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_POST['edit_calendar_event'])){

    $calendar_event_id = intval($_POST['calendar_event_id']);
    $calendar_id = intval($_POST['calendar']);
    $title = strip_tags(mysqli_real_escape_string($mysqli,$_POST['title']));
    $start = strip_tags(mysqli_real_escape_string($mysqli,$_POST['start']));
    $end = strip_tags(mysqli_real_escape_string($mysqli,$_POST['end']));

    mysqli_query($mysqli,"UPDATE calendar_events SET calendar_event_title = '$title', calendar_event_start = '$start', calendar_event_end = '$end', calendar_id = $calendar_id WHERE calendar_event_id = $calendar_event_id");

    $_SESSION['alert_message'] = "Event modified on the calendar";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_calendar_event'])){
    $calendar_event_id = intval($_GET['delete_calendar_event']);

    mysqli_query($mysqli,"DELETE FROM calendar_events WHERE calendar_event_id = $calendar_event_id");

    $_SESSION['alert_message'] = "Event deleted on the calendar";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_ticket'])){

    $client_id = intval($_POST['client']);
    $subject = strip_tags(mysqli_real_escape_string($mysqli,$_POST['subject']));
    $details = strip_tags(mysqli_real_escape_string($mysqli,$_POST['details']));

    mysqli_query($mysqli,"INSERT INTO tickets SET ticket_subject = '$subject', ticket_details = '$details', ticket_status = 'Open', ticket_created_at = NOW(), client_id = $client_id");

    $_SESSION['alert_message'] = "Ticket created";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_POST['edit_ticket'])){

    $ticket_id = intval($_POST['ticket_id']);
    $subject = strip_tags(mysqli_real_escape_string($mysqli,$_POST['subject']));
    $details = strip_tags(mysqli_real_escape_string($mysqli,$_POST['details']));

    mysqli_query($mysqli,"UPDATE tickets SET ticket_subject = '$subject', ticket_details = '$details' WHERE ticket_id = $ticket_id");

    $_SESSION['alert_message'] = "Ticket updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_POST['add_vendor'])){

    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $account_number = strip_tags(mysqli_real_escape_string($mysqli,$_POST['account_number']));
    $address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['address']));
    $city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['city']));
    $state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['state']));
    $zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['zip']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));
    $website = strip_tags(mysqli_real_escape_string($mysqli,$_POST['website']));
    
    mysqli_query($mysqli,"INSERT INTO vendors SET vendor_name = '$name', vendor_description = '$description', vendor_address = '$address', vendor_city = '$city', vendor_state = '$state', vendor_zip = '$zip', vendor_phone = '$phone', vendor_email = '$email', vendor_website = '$website', vendor_account_number = '$account_number', vendor_created_at = UNIX_TIMESTAMP()");

    $vendor_id = mysqli_insert_id($mysqli);

    //Create Directory to store expense reciepts for that vendor
    mkdir("uploads/expenses/$vendor_id");

    $_SESSION['alert_message'] = "Vendor added";
    
    header("Location: vendors.php");

}

if(isset($_POST['edit_vendor'])){

    $vendor_id = intval($_POST['vendor_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $account_number = strip_tags(mysqli_real_escape_string($mysqli,$_POST['account_number']));
    $address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['address']));
    $city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['city']));
    $state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['state']));
    $zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['zip']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));
    $website = strip_tags(mysqli_real_escape_string($mysqli,$_POST['website']));

    mysqli_query($mysqli,"UPDATE vendors SET vendor_name = '$name', vendor_description = '$description', vendor_address = '$address', vendor_city = '$city', vendor_state = '$state', vendor_zip = '$zip', vendor_phone = '$phone', vendor_email = '$email', vendor_website = '$website', vendor_account_number = '$account_number', vendor_updated_at = UNIX_TIMESTAMP() WHERE vendor_id = $vendor_id");

    $_SESSION['alert_message'] = "Vendor modified";
    
    header("Location: vendors.php");

}

if(isset($_GET['delete_vendor'])){
    $vendor_id = intval($_GET['delete_vendor']);

    mysqli_query($mysqli,"DELETE FROM vendors WHERE vendor_id = $vendor_id");

    $_SESSION['alert_message'] = "Vendor deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_product'])){

    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $cost = strip_tags(mysqli_real_escape_string($mysqli,$_POST['cost']));

    mysqli_query($mysqli,"INSERT INTO products SET product_name = '$name', product_description = '$description', product_cost = '$cost'");

    $_SESSION['alert_message'] = "Product added";
    
    header("Location: products.php");

}

if(isset($_POST['edit_product'])){

    $product_id = intval($_POST['product_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $cost = strip_tags(mysqli_real_escape_string($mysqli,$_POST['cost']));

    mysqli_query($mysqli,"UPDATE products SET product_name = '$name', product_description = '$description', product_cost = '$cost' WHERE product_id = $product_id");

    $_SESSION['alert_message'] = "Product modified";
    
    header("Location: products.php");

}

if(isset($_GET['delete_product'])){
    $product_id = intval($_GET['delete_product']);

    mysqli_query($mysqli,"DELETE FROM products WHERE product_id = $product_id");

    $_SESSION['alert_message'] = "Product deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_mileage'])){

    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $starting_location = strip_tags(mysqli_real_escape_string($mysqli,$_POST['starting_location']));
    $destination = strip_tags(mysqli_real_escape_string($mysqli,$_POST['destination']));
    $miles = intval($_POST['miles']);
    $roundtrip = intval($_POST['roundtrip']);
    $purpose = strip_tags(mysqli_real_escape_string($mysqli,$_POST['purpose']));
    $client_id = intval($_POST['client']);
    $invoice_id = intval($_POST['invoice']);
    $location_id = intval($_POST['location']);
    $vendor_id = intval($_POST['vendor']);

    mysqli_query($mysqli,"INSERT INTO mileage SET mileage_date = '$date', mileage_starting_location = '$starting_location', mileage_destination = '$destination', mileage_miles = $miles, mileage_purpose = '$purpose', client_id = $client_id, invoice_id = $invoice_id, location_id = $location_id, vendor_id = $vendor_id");

    if($roundtrip == 1){
        mysqli_query($mysqli,"INSERT INTO mileage SET mileage_date = '$date', mileage_starting_location = '$destination', mileage_destination = '$starting_location', mileage_miles = $miles, mileage_purpose = '$purpose',  client_id = $client_id, invoice_id = $invoice_id, location_id = $location_id, vendor_id = $vendor_id");
    }

    $_SESSION['alert_message'] = "Mileage added";
    
    header("Location: mileage.php");

}

if(isset($_POST['edit_mileage'])){

    $mileage_id = intval($_POST['mileage_id']);
    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $starting_location = strip_tags(mysqli_real_escape_string($mysqli,$_POST['starting_location']));
    $destination = strip_tags(mysqli_real_escape_string($mysqli,$_POST['destination']));
    $miles = intval($_POST['miles']);
    $purpose = strip_tags(mysqli_real_escape_string($mysqli,$_POST['purpose']));

    mysqli_query($mysqli,"UPDATE mileage SET mileage_date = '$date', mileage_starting_location = '$starting_location', mileage_destination = '$destination', mileage_miles = $miles, mileage_purpose = '$purpose' WHERE mileage_id = $mileage_id");

    $_SESSION['alert_message'] = "Mileage modified";
    
    header("Location: mileage.php");

}

if(isset($_GET['delete_mileage'])){
    $mileage_id = intval($_GET['delete_mileage']);

    mysqli_query($mysqli,"DELETE FROM mileage WHERE mileage_id = $mileage_id");

    $_SESSION['alert_message'] = "Mileage deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_account'])){

    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $opening_balance = $_POST['opening_balance'];

    mysqli_query($mysqli,"INSERT INTO accounts SET account_name = '$name', opening_balance = '$opening_balance'");

    $_SESSION['alert_message'] = "Account added";
    
    header("Location: accounts.php");

}

if(isset($_POST['edit_account'])){

    $account_id = intval($_POST['account_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));

    mysqli_query($mysqli,"UPDATE accounts SET account_name = '$name' WHERE account_id = $account_id");

    $_SESSION['alert_message'] = "Account modified";
    
    header("Location: accounts.php");

}

if(isset($_GET['delete_account'])){
    $account_id = intval($_GET['delete_account']);

    mysqli_query($mysqli,"DELETE FROM accounts WHERE account_id = $account_id");

    $_SESSION['alert_message'] = "Account deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_category'])){

    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $type = strip_tags(mysqli_real_escape_string($mysqli,$_POST['type']));
    $color = strip_tags(mysqli_real_escape_string($mysqli,$_POST['color']));

    mysqli_query($mysqli,"INSERT INTO categories SET category_name = '$name', category_type = '$type', category_color = '$color'");

    $_SESSION['alert_message'] = "Category added";
    
    header("Location: categories.php");

}

if(isset($_POST['edit_category'])){

    $category_id = intval($_POST['category_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $type = strip_tags(mysqli_real_escape_string($mysqli,$_POST['type']));
    $color = strip_tags(mysqli_real_escape_string($mysqli,$_POST['color']));

    mysqli_query($mysqli,"UPDATE categories SET category_name = '$name', category_type = '$type', category_color = '$color' WHERE category_id = $category_id");

    $_SESSION['alert_message'] = "Category modified";
    
    header("Location: categories.php");

}

if(isset($_GET['delete_category'])){
    $category_id = intval($_GET['delete_category']);

    mysqli_query($mysqli,"DELETE FROM categories WHERE category_id = $category_id");

    $_SESSION['alert_message'] = "Category deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_GET['alert_ack'])){

    $alert_id = intval($_GET['alert_ack']);

    mysqli_query($mysqli,"UPDATE alerts SET alert_ack_date = CURDATE() WHERE alert_id = $alert_id");

    $_SESSION['alert_message'] = "Alert Acknowledged";
    
    header("Location: alerts.php");

}

if(isset($_GET['ack_all_alerts'])){

    $sql = mysqli_query($mysqli,"SELECT * FROM alerts ORDER BY alert_id DESC");
    
    while($row = mysqli_fetch_array($sql)){
        $alert_id = $row['alert_id'];
        $alert_ack_date = $row['alert_ack_date'];

        if($alert_ack_date = 0 ){
            mysqli_query($mysqli,"UPDATE alerts SET alert_ack_date = CURDATE() WHERE alert_id = $alert_id");
        }
    }
    
    $_SESSION['alert_message'] = "Alerts Acknowledged";
    
    header("Location: alerts.php");

}

if(isset($_POST['add_expense'])){

    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $amount = $_POST['amount'];
    $account = intval($_POST['account']);
    $vendor = intval($_POST['vendor']);
    $category = intval($_POST['category']);
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $reference = strip_tags(mysqli_real_escape_string($mysqli,$_POST['reference']));

    if($_FILES['file']['tmp_name']!='') {
        $path = "uploads/expenses/$vendor/";
        $path = $path . basename( $_FILES['file']['name']);
        $file_name = basename($path);
        move_uploaded_file($_FILES['file']['tmp_name'], $path);
    }

    mysqli_query($mysqli,"INSERT INTO expenses SET expense_date = '$date', expense_amount = '$amount', account_id = $account, vendor_id = $vendor, category_id = $category, expense_description = '$description', expense_reference = '$reference', expense_receipt = '$path'");

    $_SESSION['alert_message'] = "Expense added";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_POST['edit_expense'])){

    $expense_id = intval($_POST['expense_id']);
    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $amount = $_POST['amount'];
    $account = intval($_POST['account']);
    $vendor = intval($_POST['vendor']);
    $category = intval($_POST['category']);
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $reference = strip_tags(mysqli_real_escape_string($mysqli,$_POST['reference']));

    mysqli_query($mysqli,"UPDATE expenses SET expense_date = '$date', expense_amount = '$amount', account_id = $account, vendor_id = $vendor, category_id = $category, expense_description = '$description', expense_reference = '$reference' WHERE expense_id = $expense_id");

    $_SESSION['alert_message'] = "Expense modified";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_expense'])){
    $expense_id = intval($_GET['delete_expense']);

    mysqli_query($mysqli,"DELETE FROM expenses WHERE expense_id = $expense_id");

    $_SESSION['alert_message'] = "Expense deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_transfer'])){

    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $amount = $_POST['amount'];
    $account_from = intval($_POST['account_from']);
    $account_to = intval($_POST['account_to']);

    mysqli_query($mysqli,"INSERT INTO expenses SET expense_date = '$date', expense_amount = '$amount', vendor_id = 0, account_id = $account_from");
    $expense_id = mysqli_insert_id($mysqli);
    
    mysqli_query($mysqli,"INSERT INTO payments SET payment_date = '$date', payment_amount = '$amount', account_id = $account_to, invoice_id = 0");
    $payment_id = mysqli_insert_id($mysqli);

    mysqli_query($mysqli,"INSERT INTO transfers SET transfer_date = '$date', transfer_amount = '$amount', transfer_account_from = $account_from, transfer_account_to = $account_to, expense_id = $expense_id, payment_id = $payment_id");

    $_SESSION['alert_message'] = "Transfer added";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_POST['edit_transfer'])){

    $transfer_id = intval($_POST['transfer_id']);
    $expense_id = intval($_POST['expense_id']);
    $payment_id = intval($_POST['payment_id']);
    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $amount = $_POST['amount'];
    $account_from = intval($_POST['account_from']);
    $account_to = intval($_POST['account_to']);

    mysqli_query($mysqli,"UPDATE expenses SET expense_date = '$date', expense_amount = '$amount', account_id = $account_from WHERE expense_id = $expense_id");

    mysqli_query($mysqli,"UPDATE payments SET payment_date = '$date', payment_amount = '$amount', account_id = $account_to WHERE payment_id = $payment_id");

    mysqli_query($mysqli,"UPDATE transfers SET transfer_date = '$date', transfer_amount = '$amount', transfer_account_from = $account_from, transfer_account_to = $account_to WHERE transfer_id = $transfer_id");

    $_SESSION['alert_message'] = "Transfer modified";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_transfer'])){
    $transfer_id = intval($_GET['delete_transfer']);

    //Query the transfer ID to get the Pyament and Expense IDs so we can delete those as well
    $sql = mysqli_query($mysqli,"SELECT * FROM transfers WHERE transfer_id = $transfer_id");
    $row = mysqli_fetch_array($sql);
    $expense_id = $row['expense_id'];
    $payment_id = $row['payment_id'];

    mysqli_query($mysqli,"DELETE FROM expenses WHERE expense_id = $expense_id");

    mysqli_query($mysqli,"DELETE FROM payments WHERE payment_id = $payment_id");

    mysqli_query($mysqli,"DELETE FROM transfers WHERE transfer_id = $transfer_id");

    $_SESSION['alert_message'] = "Transfer deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_invoice'])){
    $client = intval($_POST['client']);
    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $due = strip_tags(mysqli_real_escape_string($mysqli,$_POST['due']));
    $category = intval($_POST['category']);
    
    //Get the last Invoice Number and add 1 for the new invoice number
    $sql = mysqli_query($mysqli,"SELECT invoice_number FROM invoices ORDER BY invoice_number DESC LIMIT 1");
    $row = mysqli_fetch_array($sql);
    $invoice_number = $row['invoice_number'] + 1;
    mysqli_query($mysqli,"INSERT INTO invoices SET invoice_number = $invoice_number, invoice_date = '$date', invoice_due = '$due', category_id = $category, invoice_status = 'Draft', client_id = $client");
    $invoice_id = mysqli_insert_id($mysqli);
    mysqli_query($mysqli,"INSERT INTO invoice_history SET invoice_history_date = CURDATE(), invoice_history_status = 'Draft', invoice_history_description = 'INVOICE added!', invoice_id = $invoice_id");
    $_SESSION['alert_message'] = "Invoice added";
    
    header("Location: invoice.php?invoice_id=$invoice_id");
}

if(isset($_POST['edit_invoice'])){

    $invoice_id = intval($_POST['invoice_id']);
    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $due = strip_tags(mysqli_real_escape_string($mysqli,$_POST['due']));
    $category = intval($_POST['category']);

    mysqli_query($mysqli,"UPDATE invoices SET invoice_date = '$date', invoice_due = '$due', category_id = $category WHERE invoice_id = $invoice_id");

    $_SESSION['alert_message'] = "Invoice modified";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_POST['add_invoice_copy'])){

    $invoice_id = intval($_POST['invoice_id']);
    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $due = strip_tags(mysqli_real_escape_string($mysqli,$_POST['due']));
    
    //Get the last Invoice Number and add 1 for the new invoice number
    $sql = mysqli_query($mysqli,"SELECT invoice_number FROM invoices ORDER BY invoice_number DESC LIMIT 1");
    $row = mysqli_fetch_array($sql);
    $invoice_number = $row['invoice_number'] + 1;

    $sql = mysqli_query($mysqli,"SELECT * FROM invoices WHERE invoice_id = $invoice_id");
    $row = mysqli_fetch_array($sql);
    $invoice_amount = $row['invoice_amount'];
    $invoice_note = $row['invoice_note'];
    $client_id = $row['client_id'];
    $category_id = $row['category_id'];

    mysqli_query($mysqli,"INSERT INTO invoices SET invoice_number = $invoice_number, invoice_date = '$date', invoice_due = '$due', category_id = $category_id, invoice_status = 'Draft', invoice_amount = '$invoice_amount', invoice_note = '$invoice_note', client_id = $client_id");

    $new_invoice_id = mysqli_insert_id($mysqli);

    mysqli_query($mysqli,"INSERT INTO invoice_history SET invoice_history_date = CURDATE(), invoice_history_status = 'Draft', invoice_history_description = 'INVOICE added!', invoice_id = $new_invoice_id");

    $sql_invoice_items = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE invoice_id = $invoice_id");
    while($row = mysqli_fetch_array($sql_invoice_items)){
        $invoice_item_id = $row['invoice_item_id'];
        $invoice_item_name = $row['invoice_item_name'];
        $invoice_item_description = $row['invoice_item_description'];
        $invoice_item_quantity = $row['invoice_item_quantity'];
        $invoice_item_price = $row['invoice_item_price'];
        $invoice_item_subtotal = $row['invoice_item_subtotal'];
        $invoice_item_tax = $row['invoice_item_tax'];
        $invoice_item_total = $row['invoice_item_total'];

        mysqli_query($mysqli,"INSERT INTO invoice_items SET invoice_item_name = '$invoice_item_name', invoice_item_description = '$invoice_item_description', invoice_item_quantity = $invoice_item_quantity, invoice_item_price = '$invoice_item_price', invoice_item_subtotal = '$invoice_item_subtotal', invoice_item_tax = '$invoice_item_tax', invoice_item_total = '$invoice_item_total', invoice_id = $new_invoice_id");
    }

    $_SESSION['alert_message'] = "Invoice copied";
    
    header("Location: invoice.php?invoice_id=$new_invoice_id");

}

if(isset($_POST['add_quote'])){

    $client = intval($_POST['client']);
    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $category = intval($_POST['category']);
    
    //Get the last Invoice Number and add 1 for the new invoice number
    $sql = mysqli_query($mysqli,"SELECT quote_number FROM quotes ORDER BY quote_number DESC LIMIT 1");
    $row = mysqli_fetch_array($sql);
    $quote_number = $row['quote_number'] + 1;

    mysqli_query($mysqli,"INSERT INTO quotes SET quote_number = $quote_number, quote_date = '$date', category_id = $category, quote_status = 'Draft', client_id = $client");

    $quote_id = mysqli_insert_id($mysqli);

    //mysqli_query($mysqli,"INSERT INTO invoice_history SET invoice_history_date = CURDATE(), invoice_history_status = 'Draft', invoice_history_description = 'INVOICE added!', invoice_id = $invoice_id");

    $_SESSION['alert_message'] = "Quote added";
    
    header("Location: quote.php?quote_id=$quote_id");

}

if(isset($_POST['add_quote_item'])){

    $quote_id = intval($_POST['quote_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $qty = $_POST['qty'];
    $price = $_POST['price'];
    $tax = $_POST['tax'];
    
    $subtotal = $price * $qty;
    $tax = $subtotal * $tax;
    $total = $subtotal + $tax;

    mysqli_query($mysqli,"INSERT INTO invoice_items SET invoice_item_name = '$name', invoice_item_description = '$description', invoice_item_quantity = $qty, invoice_item_price = '$price', invoice_item_subtotal = '$subtotal', invoice_item_tax = '$tax', invoice_item_total = '$total', quote_id = $quote_id");

    //Update Invoice Balances

    $sql = mysqli_query($mysqli,"SELECT * FROM quotes WHERE quote_id = $quote_id");
    $row = mysqli_fetch_array($sql);

    $new_quote_amount = $row['quote_amount'] + $total;

    mysqli_query($mysqli,"UPDATE quotes SET quote_amount = '$new_quote_amount' WHERE quote_id = $quote_id");

    $_SESSION['alert_message'] = "Item added";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_quote_item'])){
    $invoice_item_id = intval($_GET['delete_quote_item']);

    $sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE invoice_item_id = $invoice_item_id");
    $row = mysqli_fetch_array($sql);
    $quote_id = $row['quote_id'];
    $invoice_item_subtotal = $row['invoice_item_subtotal'];
    $invoice_item_tax = $row['invoice_item_tax'];
    $invoice_item_total = $row['invoice_item_total'];

    $sql = mysqli_query($mysqli,"SELECT * FROM quotes WHERE quote_id = $quote_id");
    $row = mysqli_fetch_array($sql);
    
    $new_quote_amount = $row['quote_amount'] - $invoice_item_total;

    mysqli_query($mysqli,"UPDATE quotes SET quote_amount = '$new_quote_amount' WHERE quote_id = $quote_id");

    mysqli_query($mysqli,"DELETE FROM invoice_items WHERE invoice_item_id = $invoice_item_id");

    $_SESSION['alert_message'] = "Item deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_recurring_invoice'])){

    $client = intval($_POST['client']);
    $frequency = strip_tags(mysqli_real_escape_string($mysqli,$_POST['frequency']));
    $start_date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['start_date']));
    $category = intval($_POST['category']);

    mysqli_query($mysqli,"INSERT INTO invoices SET category_id = $category, invoice_status = 'Draft', client_id = $client");

    $invoice_id = mysqli_insert_id($mysqli);

    mysqli_query($mysqli,"INSERT INTO recurring_invoices SET recurring_invoice_frequency = '$frequency', recurring_invoice_start_date = '$start_date', recurring_invoice_next_date = '$start_date', recurring_invoice_status = 1, invoice_id = $invoice_id");

    $recurring_invoice_id = mysqli_insert_id($mysqli);

    $_SESSION['alert_message'] = "Recurring Invoice added";
    
    header("Location: recurring_invoice.php?recurring_invoice_id=$recurring_invoice_id");

}

if(isset($_GET['recurring_activate'])){

    $recurring_invoice_id = intval($_GET['recurring_activate']);

    mysqli_query($mysqli,"UPDATE recurring_invoices SET recurring_invoice_status = 1 WHERE recurring_invoice_id = $recurring_invoice_id");

    $_SESSION['alert_message'] = "Recurring Invoice Activated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['recurring_deactivate'])){

    $recurring_invoice_id = intval($_GET['recurring_deactivate']);

    mysqli_query($mysqli,"UPDATE recurring_invoices SET recurring_invoice_status = 0 WHERE recurring_invoice_id = $recurring_invoice_id");

    $_SESSION['alert_message'] = "Recurring Invoice Deactivated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['mark_invoice_sent'])){

    $invoice_id = intval($_GET['mark_invoice_sent']);

    mysqli_query($mysqli,"UPDATE invoices SET invoice_status = 'Sent' WHERE invoice_id = $invoice_id");

    mysqli_query($mysqli,"INSERT INTO invoice_history SET invoice_history_date = CURDATE(), invoice_history_status = 'Sent', invoice_history_description = 'INVOICE marked sent', invoice_id = $invoice_id");

    $_SESSION['alert_message'] = "Invoice marked sent";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['cancel_invoice'])){

    $invoice_id = intval($_GET['cancel_invoice']);

    mysqli_query($mysqli,"UPDATE invoices SET invoice_status = 'Cancelled' WHERE invoice_id = $invoice_id");

    mysqli_query($mysqli,"INSERT INTO invoice_history SET invoice_history_date = CURDATE(), invoice_history_status = 'Cancelled', invoice_history_description = 'INVOICE cancelled!', invoice_id = $invoice_id");

    $_SESSION['alert_message'] = "Invoice cancelled";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_invoice'])){
    $invoice_id = intval($_GET['delete_invoice']);

    mysqli_query($mysqli,"DELETE FROM invoices WHERE invoice_id = $invoice_id");

    $_SESSION['alert_message'] = "Invoice deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_invoice_item'])){

    $invoice_id = intval($_POST['invoice_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $qty = $_POST['qty'];
    $price = $_POST['price'];
    $tax = $_POST['tax'];
    
    $subtotal = $price * $qty;
    $tax = $subtotal * $tax;
    $total = $subtotal + $tax;

    mysqli_query($mysqli,"INSERT INTO invoice_items SET invoice_item_name = '$name', invoice_item_description = '$description', invoice_item_quantity = $qty, invoice_item_price = '$price', invoice_item_subtotal = '$subtotal', invoice_item_tax = '$tax', invoice_item_total = '$total', invoice_id = $invoice_id");

    //Update Invoice Balances

    $sql = mysqli_query($mysqli,"SELECT * FROM invoices WHERE invoice_id = $invoice_id");
    $row = mysqli_fetch_array($sql);

    $new_invoice_amount = $row['invoice_amount'] + $total;

    mysqli_query($mysqli,"UPDATE invoices SET invoice_amount = '$new_invoice_amount' WHERE invoice_id = $invoice_id");

    $_SESSION['alert_message'] = "Item added";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_invoice_item'])){
    $invoice_item_id = intval($_GET['delete_invoice_item']);

    $sql = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE invoice_item_id = $invoice_item_id");
    $row = mysqli_fetch_array($sql);
    $invoice_id = $row['invoice_id'];
    $invoice_item_subtotal = $row['invoice_item_subtotal'];
    $invoice_item_tax = $row['invoice_item_tax'];
    $invoice_item_total = $row['invoice_item_total'];

    $sql = mysqli_query($mysqli,"SELECT * FROM invoices WHERE invoice_id = $invoice_id");
    $row = mysqli_fetch_array($sql);
    
    $new_invoice_amount = $row['invoice_amount'] - $invoice_item_total;

    mysqli_query($mysqli,"UPDATE invoices SET invoice_amount = '$new_invoice_amount' WHERE invoice_id = $invoice_id");

    mysqli_query($mysqli,"DELETE FROM invoice_items WHERE invoice_item_id = $invoice_item_id");

    $_SESSION['alert_message'] = "Item deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_payment'])){

    $invoice_id = intval($_POST['invoice_id']);
    $balance = $_POST['balance'];
    $date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date']));
    $amount = $_POST['amount'];
    $account = intval($_POST['account']);
    $payment_method = strip_tags(mysqli_real_escape_string($mysqli,$_POST['payment_method']));
    $reference = strip_tags(mysqli_real_escape_string($mysqli,$_POST['reference']));
    $email_receipt = intval($_POST['email_receipt']);

    //Check to see if amount entered is greater than the balance of the invoice
    if($amount > $balance){
        $_SESSION['alert_message'] = "Payment is more than the balance";
        header("Location: " . $_SERVER["HTTP_REFERER"]);
    }else{
        mysqli_query($mysqli,"INSERT INTO payments SET payment_date = '$date', payment_amount = '$amount', account_id = $account, payment_method = '$payment_method', payment_reference = '$reference', invoice_id = $invoice_id");

        //Add up all the payments for the invoice and get the total amount paid to the invoice
        $sql_total_payments_amount = mysqli_query($mysqli,"SELECT SUM(payment_amount) AS payments_amount FROM payments WHERE invoice_id = $invoice_id");
        $row = mysqli_fetch_array($sql_total_payments_amount);
        $total_payments_amount = $row['payments_amount'];
        
        //Get the invoice total
        $sql = mysqli_query($mysqli,"SELECT * FROM invoices, clients WHERE invoices.client_id = clients.client_id AND invoices.invoice_id = $invoice_id");
        $row = mysqli_fetch_array($sql);
        $invoice_amount = $row['invoice_amount'];
        $invoice_number = $row['invoice_number'];
        $client_name = $row['client_name'];
        $client_email = $row['client_email'];

        //Calculate the Invoice balance
        $invoice_balance = $invoice_amount - $total_payments_amount;

            
        //Determine if invoice has been paid then set the status accordingly
        if($invoice_balance == 0){
            $invoice_status = "Paid";        
            if($email_receipt == 1){
                $mail = new PHPMailer(true);

                try {

                  //Mail Server Settings

                  //$mail->SMTPDebug = 2;                                       // Enable verbose debug output
                  $mail->isSMTP();                                            // Set mailer to use SMTP
                  $mail->Host       = $config_smtp_host;  // Specify main and backup SMTP servers
                  $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                  $mail->Username   = $config_smtp_username;                     // SMTP username
                  $mail->Password   = $config_smtp_password;                               // SMTP password
                  $mail->SMTPSecure = 'tls';                                  // Enable TLS encryption, `ssl` also accepted
                  $mail->Port       = $config_smtp_port;                                    // TCP port to connect to

                  //Recipients
                  $mail->setFrom($config_mail_from_email, $config_mail_from_name);
                  $mail->addAddress("$client_email", "$client_name");     // Add a recipient

                  // Content
                  $mail->isHTML(true);                                  // Set email format to HTML
                  $mail->Subject = "Thank You! - Payment Recieved for Invoice INV-$invoice_number";
                  $mail->Body    = "Hello $client_name,<br><br>We have recieved your payment of $amount on $date for invoice INV-$invoice_number by $payment_method<br><br>If you have any questions please contact us at the number below.<br><br>~<br>$config_company_name<br>Automated Billing Department<br>$config_company_phone";
                  //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

                  $mail->send();
                  echo 'Message has been sent';

                  mysqli_query($mysqli,"INSERT INTO invoice_history SET invoice_history_date = CURDATE(), invoice_history_status = 'Sent', invoice_history_description = 'Emailed Receipt!', invoice_id = $invoice_id");

                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            }

           

        }else{
            $invoice_status = "Partial";
            if($email_receipt == 1){
                $mail = new PHPMailer(true);

                try {

                  //Mail Server Settings

                  //$mail->SMTPDebug = 2;                                       // Enable verbose debug output
                  $mail->isSMTP();                                            // Set mailer to use SMTP
                  $mail->Host       = $config_smtp_host;  // Specify main and backup SMTP servers
                  $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                  $mail->Username   = $config_smtp_username;                     // SMTP username
                  $mail->Password   = $config_smtp_password;                               // SMTP password
                  $mail->SMTPSecure = 'tls';                                  // Enable TLS encryption, `ssl` also accepted
                  $mail->Port       = $config_smtp_port;                                    // TCP port to connect to

                  //Recipients
                  $mail->setFrom($config_mail_from_email, $config_mail_from_name);
                  $mail->addAddress("$client_email", "$client_name");     // Add a recipient

                  // Content
                  $mail->isHTML(true);                                  // Set email format to HTML
                  $mail->Subject = "Thank You! - Partial Payment Recieved for Invoice INV-$invoice_number";
                  $mail->Body    = "Hello $client_name,<br><br>We have recieved your payment of $amount on $date for invoice INV-$invoice_number by $payment_method, although you still have a balance of $invoice_balance<br><br>If you have any questions please contact us at the number below.<br><br>~<br>$config_company_name<br>Automated Billing Department<br>$config_company_phone";
                  //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

                  $mail->send();
                  echo 'Message has been sent';

                  mysqli_query($mysqli,"INSERT INTO invoice_history SET invoice_history_date = CURDATE(), invoice_history_status = 'Sent', invoice_history_description = 'Emailed Receipt!', invoice_id = $invoice_id");

                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            }

        }

        //Update Invoice Status
        mysqli_query($mysqli,"UPDATE invoices SET invoice_status = '$invoice_status' WHERE invoice_id = $invoice_id");

        //Add Payment to History
        mysqli_query($mysqli,"INSERT INTO invoice_history SET invoice_history_date = CURDATE(), invoice_history_status = '$invoice_status', invoice_history_description = 'INVOICE payment added', invoice_id = $invoice_id");

        $_SESSION['alert_message'] = "Payment added";
        
        header("Location: " . $_SERVER["HTTP_REFERER"]);
    }
}

if(isset($_GET['delete_payment'])){
    $payment_id = intval($_GET['delete_payment']);

    $sql = mysqli_query($mysqli,"SELECT * FROM payments WHERE payment_id = $payment_id");
    $row = mysqli_fetch_array($sql);
    $invoice_id = $row['invoice_id'];
    $deleted_payment_amount = $row['payment_amount'];

    //Add up all the payments for the invoice and get the total amount paid to the invoice
    $sql_total_payments_amount = mysqli_query($mysqli,"SELECT SUM(payment_amount) AS total_payments_amount FROM payments WHERE invoice_id = $invoice_id");
    $row = mysqli_fetch_array($sql_total_payments_amount);
    $total_payments_amount = $row['total_payments_amount'];
    
    //Get the invoice total
    $sql = mysqli_query($mysqli,"SELECT * FROM invoices WHERE invoice_id = $invoice_id");
    $row = mysqli_fetch_array($sql);
    $invoice_amount = $row['invoice_amount'];

    //Calculate the Invoice balance
    $invoice_balance = $invoice_amount - $total_payments_amount + $deleted_payment_amount;

    //Determine if invoice has been paid
    if($invoice_balance == 0){
        $invoice_status = "Paid";
    }else{
        $invoice_status = "Partial";
    }

    //Update Invoice Status
    mysqli_query($mysqli,"UPDATE invoices SET invoice_status = '$invoice_status' WHERE invoice_id = $invoice_id");

    mysqli_query($mysqli,"DELETE FROM payments WHERE payment_id = $payment_id");

    $_SESSION['alert_message'] = "Payment deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_GET['email_invoice'])){
    $invoice_id = intval($_GET['email_invoice']);

    $sql = mysqli_query($mysqli,"SELECT * FROM invoices, clients
    WHERE invoices.client_id = clients.client_id
    AND invoices.invoice_id = $invoice_id"
    );

    $row = mysqli_fetch_array($sql);
    $invoice_id = $row['invoice_id'];
    $invoice_number = $row['invoice_number'];
    $invoice_status = $row['invoice_status'];
    $invoice_date = $row['invoice_date'];
    $invoice_due = $row['invoice_due'];
    $invoice_amount = $row['invoice_amount'];
    $client_id = $row['client_id'];
    $client_name = $row['client_name'];
    $client_address = $row['client_address'];
    $client_city = $row['client_city'];
    $client_state = $row['client_state'];
    $client_zip = $row['client_zip'];
    $client_email = $row['client_email'];
    $client_phone = $row['client_phone'];
    if(strlen($client_phone)>2){ 
    $client_phone = substr($row['client_phone'],0,3)."-".substr($row['client_phone'],3,3)."-".substr($row['client_phone'],6,4);
    }
    $client_website = $row['client_website'];

    $sql_payments = mysqli_query($mysqli,"SELECT * FROM payments, accounts WHERE payments.account_id = accounts.account_id AND payments.invoice_id = $invoice_id ORDER BY payments.payment_id DESC");

    //Add up all the payments for the invoice and get the total amount paid to the invoice
    $sql_amount_paid = mysqli_query($mysqli,"SELECT SUM(payment_amount) AS amount_paid FROM payments WHERE invoice_id = $invoice_id");
    $row = mysqli_fetch_array($sql_amount_paid);
    $amount_paid = $row['amount_paid'];

    $balance = $invoice_amount - $amount_paid;

    $sql_invoice_items = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE invoice_id = $invoice_id ORDER BY invoice_item_id ASC");

    while($row = mysqli_fetch_array($sql_invoice_items)){
        $invoice_item_id = $row['invoice_item_id'];
        $invoice_item_name = $row['invoice_item_name'];
        $invoice_item_description = $row['invoice_item_description'];
        $invoice_item_quantity = $row['invoice_item_quantity'];
        $invoice_item_price = $row['invoice_item_price'];
        $invoice_item_subtotal = $row['invoice_item_price'];
        $invoice_item_tax = $row['invoice_item_tax'];
        $invoice_item_total = $row['invoice_item_total'];
        $total_tax = $invoice_item_tax + $total_tax;
        $sub_total = $invoice_item_price * $invoice_item_quantity + $sub_total;


        $invoice_items .= "
          <tr>
            <td align='center'>$invoice_item_name</td>
            <td>$invoice_item_description</td>
            <td class='cost'>$$invoice_item_price</td>
            <td align='center'>$invoice_item_quantity</td>
            <td class='cost'>$$invoice_item_tax</td>
            <td class='cost'>$$invoice_item_total</td>
          </tr>
        ";

    }

    $html = '
    <html>
    <head>
    <style>
    body {font-family: sans-serif;
    font-size: 10pt;
    }
    p { margin: 0pt; }
    table.items {
    border: 0.1mm solid #000000;
    }
    td { vertical-align: top; }
    .items td {
    border-left: 0.1mm solid #000000;
    border-right: 0.1mm solid #000000;
    }
    table thead td { background-color: #EEEEEE;
    text-align: center;
    border: 0.1mm solid #000000;
    font-variant: small-caps;
    }
    .items td.blanktotal {
    background-color: #EEEEEE;
    border: 0.1mm solid #000000;
    background-color: #FFFFFF;
    border: 0mm none #000000;
    border-top: 0.1mm solid #000000;
    border-right: 0.1mm solid #000000;
    }
    .items td.totals {
    text-align: right;
    border: 0.1mm solid #000000;
    }
    .items td.cost {
    text-align: "." center;
    }
    </style>
    </head>
    <body>
    <!--mpdf
    <htmlpageheader name="myheader">
    <table width="100%"><tr>
    <td width="15%"><img width="75" height="75" src=" '.$config_invoice_logo.' "></img></td>
    <td width="50%"><span style="font-weight: bold; font-size: 14pt;"> '.$config_company_name.' </span><br />' .$config_company_address.' <br /> '.$config_company_city.' '.$config_company_state.' '.$config_company_zip.'<br /> '.$config_company_phone.' </td>
    <td width="35%" style="text-align: right;">Invoice No.<br /><span style="font-weight: bold; font-size: 12pt;"> INV-'.$invoice_number.' </span></td>
    </tr></table>
    </htmlpageheader>
    <htmlpagefooter name="myfooter">
    <div style="border-top: 1px solid #000000; font-size: 9pt; text-align: center; padding-top: 3mm; ">
    Page {PAGENO} of {nb}
    </div>
    </htmlpagefooter>
    <sethtmlpageheader name="myheader" value="on" show-this-page="1" />
    <sethtmlpagefooter name="myfooter" value="on" />
    mpdf-->
    <div style="text-align: right">Date: '.$invoice_date.'</div>
    <div style="text-align: right">Due: '.$invoice_due.' </div>
    <table width="100%" style="font-family: serif;" cellpadding="10"><tr>
    <td width="45%" style="border: 0.1mm solid #888888; "><span style="font-size: 7pt; color: #555555; font-family: sans;">BILL TO:</span><br /><br /><b> '.$client_name.' </b><br />'.$client_address.'<br />'.$client_city.' '.$client_state.' '.$client_zip.' <br /><br> '.$client_email.' <br /> '.$client_phone.'</td>
    <td width="65%">&nbsp;</td>

    </tr></table>
    <br />
    <table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse; " cellpadding="8">
    <thead>
    <tr>
    <td width="20%">Item</td>
    <td width="25%">Description</td>
    <td width="15%">Unit Cost</td>
    <td width="10%">Quantity</td>
    <td width="15%">Tax</td>
    <td width="15%">Line Total</td>
    </tr>
    </thead>
    <tbody>
    '.$invoice_items.'
    <tr>
    <td class="blanktotal" colspan="4" rowspan="5"><h4>Notes</h4> '.$invoice_note.' </td>
    <td class="totals">Subtotal:</td>
    <td class="totals cost">$ '.number_format($sub_total,2).' </td>
    </tr>
    <tr>
    <td class="totals">Tax:</td>
    <td class="totals cost">$ '.number_format($total_tax,2).' </td>
    </tr>
    <tr>
    <td class="totals">Total:</td>
    <td class="totals cost">$ '.number_format($invoice_amount,2).' </td>
    </tr>
    <tr>
    <td class="totals">Paid:</td>
    <td class="totals cost">$ '.number_format($amount_paid,2).' </td>
    </tr>
    <tr>
    <td class="totals"><b>Balance due:</b></td>
    <td class="totals cost"><b>$ '.number_format($balance,2).' </b></td>
    </tr>
    </tbody>
    </table>
    <div style="text-align: center; font-style: italic;"> '.$config_invoice_footer.' </div>
    </body>
    </html>
    ';
    
    $mpdf = new \Mpdf\Mpdf([
    'margin_left' => 20,
    'margin_right' => 15,
    'margin_top' => 48,
    'margin_bottom' => 25,
    'margin_header' => 10,
    'margin_footer' => 10
    ]);
    $mpdf->SetProtection(array('print'));
    $mpdf->SetTitle("$config_company_name - Invoice");
    $mpdf->SetAuthor("$config_company_name");
    if($invoice_status == 'Paid'){
    $mpdf->SetWatermarkText("Paid");
    }
    $mpdf->showWatermarkText = true;
    $mpdf->watermark_font = 'DejaVuSansCondensed';
    $mpdf->watermarkTextAlpha = 0.1;
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->WriteHTML($html);
    $mpdf->Output("uploads/$invoice_date-$config_company_name-Invoice$invoice_number.pdf", 'F');

    $mail = new PHPMailer(true);

    try{

        //Mail Server Settings

        //$mail->SMTPDebug = 2;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host       = $config_smtp_host;  // Specify main and backup SMTP servers
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = $config_smtp_username;                     // SMTP username
        $mail->Password   = $config_smtp_password;                               // SMTP password
        $mail->SMTPSecure = 'tls';                                  // Enable TLS encryption, `ssl` also accepted
        $mail->Port       = $config_smtp_port;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom($config_mail_from_email, $config_mail_from_name);
        $mail->addAddress("$client_email", "$client_name");     // Add a recipient

        // Attachments
        //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        $mail->addAttachment("uploads/$invoice_date-$config_company_name-Invoice$invoice_number.pdf");    // Optional name

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = "Invoice $invoice_number - $invoice_date - Due $invoice_due";
        $mail->Body    = "Hello $client_name,<br><br>Thank you for choosing $config_company_name! -- attached to this email is your invoice in PDF form due on <b>$invoice_due</b> Please make all checks payable to $config_company_name and mail to $config_company_address $config_company_city $config_company_state $config_company_zip before <b>$invoice_due</b>.<br><br>If you have any questions please contact us at the number below.<br><br>~<br>$config_company_name<br>Automated Billing Department<br>$config_company_phone";
        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        $mail->send();
        echo 'Message has been sent';

        mysqli_query($mysqli,"INSERT INTO invoice_history SET invoice_history_date = CURDATE(), invoice_history_status = 'Sent', invoice_history_description = 'Emailed Invoice!', invoice_id = $invoice_id");

        //Don't chnage the status to sent if the status is anything but draf
        if($invoice_status == 'Draft'){

            mysqli_query($mysqli,"UPDATE invoices SET invoice_status = 'Sent', client_id = $client_id WHERE invoice_id = $invoice_id");

        }

        $_SESSION['alert_message'] = "Invoice has been sent";

        header("Location: " . $_SERVER["HTTP_REFERER"]);


    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
    unlink("uploads/$invoice_date-$config_company_name-Invoice$invoice_number.pdf");
}

if(isset($_GET['pdf_invoice'])){

    $invoice_id = intval($_GET['pdf_invoice']);

    $sql = mysqli_query($mysqli,"SELECT * FROM invoices, clients
    WHERE invoices.client_id = clients.client_id
    AND invoices.invoice_id = $invoice_id"
    );

    $row = mysqli_fetch_array($sql);
    $invoice_id = $row['invoice_id'];
    $invoice_number = $row['invoice_number'];
    $invoice_status = $row['invoice_status'];
    $invoice_date = $row['invoice_date'];
    $invoice_due = $row['invoice_due'];
    $invoice_amount = $row['invoice_amount'];
    $invoice_note = $row['invoice_note'];
    $invoice_category_id = $row['category_id'];
    $client_id = $row['client_id'];
    $client_name = $row['client_name'];
    $client_address = $row['client_address'];
    $client_city = $row['client_city'];
    $client_state = $row['client_state'];
    $client_zip = $row['client_zip'];
    $client_email = $row['client_email'];
    $client_phone = $row['client_phone'];
    if(strlen($client_phone)>2){ 
    $client_phone = substr($row['client_phone'],0,3)."-".substr($row['client_phone'],3,3)."-".substr($row['client_phone'],6,4);
    }
    $client_website = $row['client_website'];

    $sql_payments = mysqli_query($mysqli,"SELECT * FROM payments, accounts WHERE payments.account_id = accounts.account_id AND payments.invoice_id = $invoice_id ORDER BY payments.payment_id DESC");

    //Add up all the payments for the invoice and get the total amount paid to the invoice
    $sql_amount_paid = mysqli_query($mysqli,"SELECT SUM(payment_amount) AS amount_paid FROM payments WHERE invoice_id = $invoice_id");
    $row = mysqli_fetch_array($sql_amount_paid);
    $amount_paid = $row['amount_paid'];

    $balance = $invoice_amount - $amount_paid;

    $sql_invoice_items = mysqli_query($mysqli,"SELECT * FROM invoice_items WHERE invoice_id = $invoice_id ORDER BY invoice_item_id ASC");

    while($row = mysqli_fetch_array($sql_invoice_items)){
        $invoice_item_id = $row['invoice_item_id'];
        $invoice_item_name = $row['invoice_item_name'];
        $invoice_item_description = $row['invoice_item_description'];
        $invoice_item_quantity = $row['invoice_item_quantity'];
        $invoice_item_price = $row['invoice_item_price'];
        $invoice_item_subtotal = $row['invoice_item_price'];
        $invoice_item_tax = $row['invoice_item_tax'];
        $invoice_item_total = $row['invoice_item_total'];
        $total_tax = $invoice_item_tax + $total_tax;
        $sub_total = $invoice_item_price * $invoice_item_quantity + $sub_total;

        $invoice_items .= "
        <tr>
            <td align='center'>$invoice_item_name</td>
            <td>$invoice_item_description</td>
            <td class='cost'>$$invoice_item_price</td>
            <td align='center'>$invoice_item_quantity</td>
            <td class='cost'>$$invoice_item_tax</td>
            <td class='cost'>$$invoice_item_total</td>
        </tr>
        ";

    }

    $html = '
        <html>
        <head>
        <style>
        body {font-family: sans-serif;
          font-size: 10pt;
        }
        p { margin: 0pt; }
        table.items {
          border: 0.1mm solid #000000;
        }
        td { vertical-align: top; }
        .items td {
          border-left: 0.1mm solid #000000;
          border-right: 0.1mm solid #000000;
        }
        table thead td { background-color: #EEEEEE;
          text-align: center;
          border: 0.1mm solid #000000;
          font-variant: small-caps;
        }
        .items td.blanktotal {
          background-color: #EEEEEE;
          border: 0.1mm solid #000000;
          background-color: #FFFFFF;
          border: 0mm none #000000;
          border-top: 0.1mm solid #000000;
          border-right: 0.1mm solid #000000;
        }
        .items td.totals {
          text-align: right;
          border: 0.1mm solid #000000;
        }
        .items td.cost {
          text-align: "." center;
        }
        </style>
        </head>
        <body>
        <!--mpdf
        <htmlpageheader name="myheader">
        <table width="100%"><tr>
        <td width="15%"><img width="75" height="75" src=" '.$config_invoice_logo.' "></img></td>
        <td width="50%"><span style="font-weight: bold; font-size: 14pt;"> '.$config_company_name.' </span><br />' .$config_company_address.' <br /> '.$config_company_city.' '.$config_company_state.' '.$config_company_zip.'<br /> '.$config_company_phone.' </td>
        <td width="35%" style="text-align: right;">Invoice No.<br /><span style="font-weight: bold; font-size: 12pt;"> INV-'.$invoice_number.' </span></td>
        </tr></table>
        </htmlpageheader>
        <htmlpagefooter name="myfooter">
        <div style="border-top: 1px solid #000000; font-size: 9pt; text-align: center; padding-top: 3mm; ">
        Page {PAGENO} of {nb}
        </div>
        </htmlpagefooter>
        <sethtmlpageheader name="myheader" value="on" show-this-page="1" />
        <sethtmlpagefooter name="myfooter" value="on" />
        mpdf-->
        <div style="text-align: right">Date: '.$invoice_date.'</div>
        <div style="text-align: right">Due: '.$invoice_due.'</div>
        <table width="100%" style="font-family: serif;" cellpadding="10"><tr>
        <td width="45%" style="border: 0.1mm solid #888888; "><span style="font-size: 7pt; color: #555555; font-family: sans;">BILL TO:</span><br /><br /><b> '.$client_name.' </b><br />'.$client_address.'<br />'.$client_city.' '.$client_state.' '.$client_zip.' <br /><br> '.$client_email.' <br /> '.$client_phone.'</td>
        <td width="65%">&nbsp;</td>

        </tr></table>
        <br />
        <table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse; " cellpadding="8">
        <thead>
        <tr>
        <td width="20%">Item</td>
        <td width="25%">Description</td>
        <td width="15%">Unit Cost</td>
        <td width="10%">Quantity</td>
        <td width="15%">Tax</td>
        <td width="15%">Line Total</td>
        </tr>
        </thead>
        <tbody>
        '.$invoice_items.'
        <tr>
        <td class="blanktotal" colspan="4" rowspan="5"><h4>Notes</h4> '.$invoice_note.' </td>
        <td class="totals">Subtotal:</td>
        <td class="totals cost">$ '.number_format($sub_total,2).' </td>
        </tr>
        <tr>
        <td class="totals">Tax:</td>
        <td class="totals cost">$ '.number_format($total_tax,2).' </td>
        </tr>
        <tr>
        <td class="totals">Total:</td>
        <td class="totals cost">$ '.number_format($invoice_amount,2).' </td>
        </tr>
        <tr>
        <td class="totals">Paid:</td>
        <td class="totals cost">$ '.number_format($amount_paid,2).' </td>
        </tr>
        <tr>
        <td class="totals"><b>Balance due:</b></td>
        <td class="totals cost"><b>$ '.number_format($balance,2).' </b></td>
        </tr>
        </tbody>
        </table>
        <div style="text-align: center; font-style: italic;"> '.$config_invoice_footer.' </div>
        </body>
        </html>
    ';

    $mpdf = new \Mpdf\Mpdf([
        'margin_left' => 20,
        'margin_right' => 15,
        'margin_top' => 48,
        'margin_bottom' => 25,
        'margin_header' => 10,
        'margin_footer' => 10
    ]);

    $mpdf->SetProtection(array('print'));
    $mpdf->SetTitle("$config_company_name - Invoice");
    $mpdf->SetAuthor("$config_company_name");
    if($invoice_status == 'Paid'){
        $mpdf->SetWatermarkText("Paid");
    }
    $mpdf->showWatermarkText = true;
    $mpdf->watermark_font = 'DejaVuSansCondensed';
    $mpdf->watermarkTextAlpha = 0.1;
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->WriteHTML($html);
    $mpdf->Output();

}

if(isset($_POST['edit_invoice_note'])){

    $invoice_id = intval($_POST['invoice_id']);
    $invoice_note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['invoice_note']));

    mysqli_query($mysqli,"UPDATE invoices SET invoice_note = '$invoice_note' WHERE invoice_id = $invoice_id");

    $_SESSION['alert_message'] = "Notes added";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_POST['add_client_contact'])){

    $client_id = intval($_POST['client_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $title = strip_tags(mysqli_real_escape_string($mysqli,$_POST['title']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));

    if($_FILES['file']['tmp_name']!='') {
        $path = "uploads/client_contact_photos/";
        $path = $path . time() . basename( $_FILES['file']['name']);
        $file_name = basename($path);
        move_uploaded_file($_FILES['file']['tmp_name'], $path);
    }

    mysqli_query($mysqli,"INSERT INTO client_contacts SET client_contact_name = '$name', client_contact_title = '$title', client_contact_phone = '$phone', client_contact_email = '$email', client_contact_photo = '$path', client_id = $client_id");

    $_SESSION['alert_message'] = "Contact added";
    
    header("Location: client.php?client_id=$client_id&tab=contacts");


}

if(isset($_POST['edit_client_contact'])){

    $client_contact_id = intval($_POST['client_contact_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $title = strip_tags(mysqli_real_escape_string($mysqli,$_POST['title']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));

    mysqli_query($mysqli,"UPDATE client_contacts SET client_contact_name = '$name', client_contact_title = '$title', client_contact_phone = '$phone', client_contact_email = '$email' WHERE client_contact_id = $client_contact_id");

    $_SESSION['alert_message'] = "Contact updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_client_contact'])){
    $client_contact_id = intval($_GET['delete_client_contact']);

    mysqli_query($mysqli,"DELETE FROM client_contacts WHERE client_contact_id = $client_contact_id");

    $_SESSION['alert_message'] = "Contact deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_client_location'])){

    $client_id = intval($_POST['client_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['address']));
    $city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['city']));
    $state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['state']));
    $zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['zip']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $hours = strip_tags(mysqli_real_escape_string($mysqli,$_POST['hours']));

    mysqli_query($mysqli,"INSERT INTO client_locations SET client_location_name = '$name', client_location_address = '$address', client_location_city = '$city', client_location_state = '$state', client_location_zip = '$zip', client_location_phone = '$phone', client_location_hours = '$hours', client_id = $client_id");

    $_SESSION['alert_message'] = "Location added";
    
    header("Location: client.php?client_id=$client_id&tab=locations");

}

if(isset($_POST['edit_client_location'])){

    $client_location_id = intval($_POST['client_location_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['address']));
    $city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['city']));
    $state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['state']));
    $zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['zip']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $hours = strip_tags(mysqli_real_escape_string($mysqli,$_POST['hours']));

    mysqli_query($mysqli,"UPDATE client_locations SET client_location_name = '$name', client_location_address = '$address', client_location_city = '$city', client_location_state = '$state', client_location_zip = '$zip', client_location_phone = '$phone', client_location_hours = '$hours' WHERE client_location_id = $client_location_id");

    $_SESSION['alert_message'] = "Location updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_client_location'])){
    $client_location_id = intval($_GET['delete_client_location']);

    mysqli_query($mysqli,"DELETE FROM client_locations WHERE client_location_id = $client_location_id");

    $_SESSION['alert_message'] = "Location deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_client_asset'])){

    $client_id = intval($_POST['client_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $type = strip_tags(mysqli_real_escape_string($mysqli,$_POST['type']));
    $make = strip_tags(mysqli_real_escape_string($mysqli,$_POST['make']));
    $model = strip_tags(mysqli_real_escape_string($mysqli,$_POST['model']));
    $serial = strip_tags(mysqli_real_escape_string($mysqli,$_POST['serial']));
    $location = intval($_POST['location']);
    $vendor = intval($_POST['vendor']);
    $contact = intval($_POST['contact']);
    $purchase_date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['purchase_date']));
    $warranty_expire = strip_tags(mysqli_real_escape_string($mysqli,$_POST['warranty_expire']));
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));

    mysqli_query($mysqli,"INSERT INTO client_assets SET client_asset_name = '$name', client_asset_type = '$type', client_asset_make = '$make', client_asset_model = '$model', client_asset_serial = '$serial', client_location_id = $location, client_vendor_id = $vendor, client_contact_id = $contact, client_asset_purchase_date = '$purchase_date', client_asset_warranty_expire = '$warranty_expire', client_asset_note = '$note', client_id = $client_id");

    if(!empty($_POST['username'])) {
        $asset_id = mysqli_insert_id($mysqli);
        $username = strip_tags(mysqli_real_escape_string($mysqli,$_POST['username']));
        $password = strip_tags(mysqli_real_escape_string($mysqli,$_POST['password']));
        $description = "$type - $name";
        mysqli_query($mysqli,"INSERT INTO client_logins SET client_login_description = '$description', client_login_username = '$username', client_login_password = '$password', client_asset_id = $asset_id, client_id = $client_id");

    }

    $_SESSION['alert_message'] = "Asset added";
    
    header("Location: client.php?client_id=$client_id&tab=assets");

}

if(isset($_POST['edit_client_asset'])){

    $asset_id = intval($_POST['client_asset_id']);
    $login_id = intval($_POST['client_login_id']);
    $client_id = intval($_POST['client_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $type = strip_tags(mysqli_real_escape_string($mysqli,$_POST['type']));
    $make = strip_tags(mysqli_real_escape_string($mysqli,$_POST['make']));
    $model = strip_tags(mysqli_real_escape_string($mysqli,$_POST['model']));
    $serial = strip_tags(mysqli_real_escape_string($mysqli,$_POST['serial']));
    $location = intval($_POST['location']);
    $vendor = intval($_POST['vendor']);
    $contact = intval($_POST['contact']);
    $purchase_date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['purchase_date']));
    $warranty_expire = strip_tags(mysqli_real_escape_string($mysqli,$_POST['warranty_expire']));
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));
    $username = strip_tags(mysqli_real_escape_string($mysqli,$_POST['username']));
    $password = strip_tags(mysqli_real_escape_string($mysqli,$_POST['password']));
    $description = "$type - $name";

    mysqli_query($mysqli,"UPDATE client_assets SET client_asset_name = '$name', client_asset_type = '$type', client_asset_make = '$make', client_asset_model = '$model', client_asset_serial = '$serial', client_location_id = $location, client_vendor_id = $vendor, client_contact_id = $contact, client_asset_purchase_date = '$purchase_date', client_asset_warranty_expire = '$warranty_expire', client_asset_note = '$note' WHERE client_asset_id = $asset_id");

    //If login exists then update the login
    if($login_id > 0){
        mysqli_query($mysqli,"UPDATE client_logins SET client_login_description = '$description', client_login_username = '$username', client_login_password = '$password' WHERE client_login_id = $login_id");
    }else{
    //If Username is filled in then add a login
        if(!empty($_POST['username'])) {
            
            mysqli_query($mysqli,"INSERT INTO client_logins SET client_login_description = '$description', client_login_username = '$username', client_login_password = '$password', client_asset_id = $asset_id, client_id = $client_id");

        }
    }

    $_SESSION['alert_message'] = "Asset updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_client_asset'])){
    $client_asset_id = intval($_GET['delete_client_asset']);

    mysqli_query($mysqli,"DELETE FROM client_assets WHERE client_asset_id = $client_asset_id");

    $_SESSION['alert_message'] = "Asset deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_client_vendor'])){

    $client_id = intval($_POST['client_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $account_number = strip_tags(mysqli_real_escape_string($mysqli,$_POST['account_number']));

    mysqli_query($mysqli,"INSERT INTO client_vendors SET client_vendor_name = '$name', client_vendor_description = '$description', client_vendor_account_number = '$account_number', client_id = $client_id");

    if(!empty($_POST['username'])) {
        $vendor_id = mysqli_insert_id($mysqli);
        $username = strip_tags(mysqli_real_escape_string($mysqli,$_POST['username']));
        $password = strip_tags(mysqli_real_escape_string($mysqli,$_POST['password']));

        mysqli_query($mysqli,"INSERT INTO client_logins SET client_login_username = '$username', client_login_password = '$password', client_vendor_id = $vendor_id, client_id = $client_id");

    }

    $_SESSION['alert_message'] = "Vendor added";
    
    header("Location: client.php?client_id=$client_id&tab=vendors");

}

if(isset($_POST['edit_client_vendor'])){

    $client_vendor_id = intval($_POST['client_vendor_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $account_number = strip_tags(mysqli_real_escape_string($mysqli,$_POST['account_number']));

    mysqli_query($mysqli,"UPDATE client_vendors SET client_vendor_name = '$name', client_vendor_description = '$description', client_vendor_account_number = '$account_number' WHERE client_vendor_id = $client_vendor_id");

    $_SESSION['alert_message'] = "Vendor updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_client_vendor'])){
    $client_vendor_id = intval($_GET['delete_client_vendor']);

    mysqli_query($mysqli,"DELETE FROM client_vendors WHERE client_vendor_id = $client_vendor_id");

    $_SESSION['alert_message'] = "Vendor deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_client_login'])){

    $client_id = intval($_POST['client_id']);
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $web_link = strip_tags(mysqli_real_escape_string($mysqli,$_POST['web_link']));
    $username = strip_tags(mysqli_real_escape_string($mysqli,$_POST['username']));
    $password = strip_tags(mysqli_real_escape_string($mysqli,$_POST['password']));
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));
    $vendor_id = intval($_POST['vendor']);
    $asset_id = intval($_POST['asset']);
    $application_id = intval($_POST['application']);

    mysqli_query($mysqli,"INSERT INTO client_logins SET client_login_description = '$description', client_login_web_link = '$web_link', client_login_username = '$username', client_login_password = '$password', client_login_note = '$note', client_vendor_id = $vendor_id, client_asset_id = $asset_id, client_application_id = $application_id, client_id = $client_id");

    $_SESSION['alert_message'] = "Login added";
    
    header("Location: client.php?client_id=$client_id&tab=logins");

}

if(isset($_POST['edit_client_login'])){

    $client_login_id = intval($_POST['client_login_id']);
    $description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['description']));
    $web_link = strip_tags(mysqli_real_escape_string($mysqli,$_POST['web_link']));
    $username = strip_tags(mysqli_real_escape_string($mysqli,$_POST['username']));
    $password = strip_tags(mysqli_real_escape_string($mysqli,$_POST['password']));
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));

    mysqli_query($mysqli,"UPDATE client_logins SET client_login_description = '$description', client_login_web_link = '$web_link', client_login_username = '$username', client_login_password = '$password', client_login_note = '$note' WHERE client_login_id = $client_login_id");

    $_SESSION['alert_message'] = "Login updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_client_login'])){
    $client_login_id = intval($_GET['delete_client_login']);

    mysqli_query($mysqli,"DELETE FROM client_logins WHERE client_login_id = $client_login_id");

    $_SESSION['alert_message'] = "Login deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_client_file'])){
    $client_id = intval($_POST['client_id']);
    $new_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['new_name']));

    if($_FILES['file']['tmp_name']!='') {
        $path = "uploads/client_files/$client_id/";
        $path = $path . basename( $_FILES['file']['name']);
        $file_name = basename($path);
        move_uploaded_file($_FILES['file']['tmp_name'], $path);
        $ext = pathinfo($path);
        $ext = $ext['extension'];

    }

    mysqli_query($mysqli,"INSERT INTO files SET file_name = '$path', file_ext = '$ext', client_id = $client_id");

    $_SESSION['alert_message'] = "File uploaded";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_file'])){
    $file_id = intval($_GET['delete_file']);

    $sql_file = mysqli_query($mysqli,"SELECT * FROM files WHERE file_id = $file_id");
    $row = mysqli_fetch_array($sql_file);
    $file_name = $row['file_name'];

    unlink($file_name);

    mysqli_query($mysqli,"DELETE FROM files WHERE file_id = $file_id");

    $_SESSION['alert_message'] = "File deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_client_note'])){

    $client_id = intval($_POST['client_id']);
    $subject = strip_tags(mysqli_real_escape_string($mysqli,$_POST['subject']));
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));

    mysqli_query($mysqli,"INSERT INTO client_notes SET client_note_subject = '$subject', client_note_body = '$note', client_id = $client_id");

    $_SESSION['alert_message'] = "Note added";
    
    header("Location: client.php?client_id=$client_id&tab=notes");

}

if(isset($_POST['edit_client_note'])){

    $client_note_id = intval($_POST['client_note_id']);
    $subject = strip_tags(mysqli_real_escape_string($mysqli,$_POST['subject']));
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));

    mysqli_query($mysqli,"UPDATE client_notes SET client_note_subject = '$subject', client_note_body = '$note' WHERE client_note_id = $client_note_id");

    $_SESSION['alert_message'] = "Note updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_client_note'])){
    $client_note_id = intval($_GET['delete_client_note']);

    mysqli_query($mysqli,"DELETE FROM client_notes WHERE client_note_id = $client_note_id");

    $_SESSION['alert_message'] = "Note deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_client_network'])){

    $client_id = intval($_POST['client_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $network = strip_tags(mysqli_real_escape_string($mysqli,$_POST['network']));
    $gateway = strip_tags(mysqli_real_escape_string($mysqli,$_POST['gateway']));
    $dhcp_range = strip_tags(mysqli_real_escape_string($mysqli,$_POST['dhcp_range']));

    mysqli_query($mysqli,"INSERT INTO client_networks SET client_network_name = '$name', client_network = '$network', client_network_gateway = '$gateway', client_network_dhcp_range = '$dhcp_range', client_id = $client_id");

    $_SESSION['alert_message'] = "Network added";
    
    header("Location: client.php?client_id=$client_id&tab=networks");

}

if(isset($_POST['edit_client_network'])){

    $client_network_id = intval($_POST['client_network_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $network = strip_tags(mysqli_real_escape_string($mysqli,$_POST['network']));
    $gateway = strip_tags(mysqli_real_escape_string($mysqli,$_POST['gateway']));
    $dhcp_range = strip_tags(mysqli_real_escape_string($mysqli,$_POST['dhcp_range']));

    mysqli_query($mysqli,"UPDATE client_networks SET client_network_name = '$name', client_network = '$network', client_network_gateway = '$gateway', client_network_dhcp_range = '$dhcp_range' WHERE client_network_id = $client_network_id");

    $_SESSION['alert_message'] = "Network updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_client_network'])){
    $client_network_id = intval($_GET['delete_client_network']);

    mysqli_query($mysqli,"DELETE FROM client_networks WHERE client_network_id = $client_network_id");

    $_SESSION['alert_message'] = "Network deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_client_domain'])){

    $client_id = intval($_POST['client_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $registrar = intval($_POST['registrar']);
    $webhost = intval($_POST['webhost']);
    $expire = strip_tags(mysqli_real_escape_string($mysqli,$_POST['expire']));

    mysqli_query($mysqli,"INSERT INTO client_domains SET client_domain_name = '$name', client_domain_registrar = $registrar,  client_domain_webhost = $webhost, client_domain_expire = '$expire', client_id = $client_id");

    $_SESSION['alert_message'] = "Domain added";
    
    header("Location: client.php?client_id=$client_id&tab=domains");

}

if(isset($_POST['edit_client_domain'])){

    $client_domain_id = intval($_POST['client_domain_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $registrar = intval($_POST['registrar']);
    $webhost = intval($_POST['webhost']);
    $expire = strip_tags(mysqli_real_escape_string($mysqli,$_POST['expire']));

    mysqli_query($mysqli,"UPDATE client_domains SET client_domain_name = '$name', client_domain_registrar = $registrar,  client_domain_webhost = $webhost, client_domain_expire = '$expire' WHERE client_domain_id = $client_domain_id");

    $_SESSION['alert_message'] = "Domain updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_client_domain'])){
    $client_domain_id = intval($_GET['delete_client_domain']);

    mysqli_query($mysqli,"DELETE FROM client_domains WHERE client_domain_id = $client_domain_id");

    $_SESSION['alert_message'] = "Domain deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['add_client_application'])){

    $client_id = intval($_POST['client_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $type = strip_tags(mysqli_real_escape_string($mysqli,$_POST['type']));
    $license = strip_tags(mysqli_real_escape_string($mysqli,$_POST['license']));

    mysqli_query($mysqli,"INSERT INTO client_applications SET client_application_name = '$name', client_application_type = '$type', client_application_license = '$license', client_id = $client_id");

    if(!empty($_POST['username'])) {
        $application_id = mysqli_insert_id($mysqli);
        $username = strip_tags(mysqli_real_escape_string($mysqli,$_POST['username']));
        $password = strip_tags(mysqli_real_escape_string($mysqli,$_POST['password']));
        
        mysqli_query($mysqli,"INSERT INTO client_logins SET client_login_username = '$username', client_login_password = '$password', client_application_id = $application_id, client_id = $client_id");

    }

    $_SESSION['alert_message'] = "Application added";
    
    header("Location: client.php?client_id=$client_id&tab=applications");

}

if(isset($_POST['edit_client_application'])){

    $client_application_id = intval($_POST['client_application_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $type = strip_tags(mysqli_real_escape_string($mysqli,$_POST['type']));
    $license = strip_tags(mysqli_real_escape_string($mysqli,$_POST['license']));

    mysqli_query($mysqli,"UPDATE client_applications SET client_application_name = '$name', client_application_type = '$type', client_application_license = '$license' WHERE client_application_id = $client_application_id");

    $_SESSION['alert_message'] = "Application updated";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if(isset($_GET['delete_client_application'])){
    $client_application_id = intval($_GET['delete_client_application']);

    mysqli_query($mysqli,"DELETE FROM client_applications WHERE client_application_id = $client_application_id");

    $_SESSION['alert_message'] = "Application deleted";
    
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  
}

if(isset($_POST['change_password'])){
    $current_url = $_POST['current_url'];
    $new_password = mysqli_real_escape_string($mysqli,$_POST['new_password']);

    $sql = mysqli_query($mysqli,"SELECT password FROM users WHERE user_id = $session_user_id");
    $row = mysqli_fetch_array($sql);
    $old_password = $row['password'];

    if($old_password == $new_password){
        $hash_password = $old_password;
    }else{
        $hash_password = md5($new_password);
    }

    mysqli_query($mysqli,"UPDATE users SET password = '$hash_password', user_modified = UNIX_TIMESTAMP() WHERE user_id = $session_user_id");
    
    $event_description = "User changed their own password.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'User Password Change', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Password Changed";
    
    header("Location: $current_url");

}

if(isset($_POST['change_location'])){
    $current_url = $_POST['current_url'];
    $new_location = intval($_POST['new_location']);

    mysqli_query($mysqli,"UPDATE users SET current_location_id = $new_location WHERE user_id = $session_user_id");
    
    $event_description = "User changed their location.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'User Location Change', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Location Changed";
    
    header("Location: $current_url");

}

if(isset($_POST['change_avatar'])){
    $avatar_path = $_POST['current_avatar_path'];
    $current_url = $_POST['current_url'];
    $check = getimagesize($_FILES["avatar"]["tmp_name"]);
    if($check !== false) {
        if($avatar_path != "img/default_user_avatar.png"){
            unlink($avatar_path);
        }
        $avatar_path = "uploads/user_avatars/";
        $avatar_path =  $avatar_path . $session_user_id . '_' . time() . '_' . basename( $_FILES['avatar']['name']);
        move_uploaded_file($_FILES['avatar']['tmp_name'], "$avatar_path");
    }

    mysqli_query($mysqli,"UPDATE users SET avatar = '$avatar_path' WHERE user_id = $session_user_id");

    $event_description = "User changed their avatar.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'User Avatar Change', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");
    
    $_SESSION['alert_message'] = "User Avatar Changed";
    
    header("Location: $current_url");

  }

if(isset($_POST['edit_candidate'])){
    $candidate_id = intval($_POST['candidate_id']);
    $first_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['first_name']));
    $last_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['last_name']));
    $address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['address']));
    $city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['city']));
    $state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['state']));
    $zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['zip']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));
    $social_security = strip_tags(mysqli_real_escape_string($mysqli,$_POST['social_security']));
    $birth_day = strip_tags(mysqli_real_escape_string($mysqli,$_POST['birth_day']));
    $location = intval($_POST['location']);
    $old_password = mysqli_real_escape_string($mysqli,$_POST['old_password']);
    $new_password = mysqli_real_escape_string($mysqli,$_POST['new_password']);
    if($old_password == $new_password){
        $hash_password = $old_password;
    }else{
        $hash_password = md5($new_password);
    }
    $avatar_path = $_POST['current_avatar_path'];
    $check = getimagesize($_FILES["avatar"]["tmp_name"]);
    if($check !== false) {
        if($avatar_path != "img/default_candidate_avatar.png"){
            unlink($avatar_path);
        }
        $avatar_path = "uploads/candidate_avatars/";
        $avatar_path =  $avatar_path . $candidate_id . '_' . time() . '_' . basename( $_FILES['avatar']['name']);
        move_uploaded_file($_FILES['avatar']['tmp_name'], "$avatar_path");
    }


    mysqli_query($mysqli,"UPDATE candidates SET first_name = '$first_name', last_name = '$last_name', address = '$address', city = '$city', state = '$state', zip = '$zip', phone = '$phone', email = '$email', password = '$hash_password', birth_day = '$birth_day', social_security = '$social_security', candidate_avatar = '$avatar_path', location_applied_at = $location, candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $event_description = "Candidate <a href=''candidate.php?candidate_id=$candidate_id''>$first_name $last_name</a> modified.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Edit Candidate', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");
    
    $_SESSION['alert_message'] = "Candidate Updated";
    
    header("Location: candidate.php?candidate_id=$candidate_id");
}

if(isset($_GET['delete_candidate'])){
    $candidate_id = intval($_GET['delete_candidate']);

    $sql = mysqli_query($mysqli,"DELETE FROM candidates WHERE candidate_id = $candidate_id");

    $event_description = "Candidate Deleted.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Delete Candidate', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    removeDirectory("uploads/candidate_files/$candidate_id");

    $_SESSION['alert_message'] = "Candidate Deleted";
    
    header("Location: candidates.php");
  
}

if(isset($_POST['change_candidate_avatar'])){
    $candidate_id = intval($_POST['candidate_id']);
    $avatar_path = $_POST['current_avatar_path'];
    $current_url = $_POST['current_url'];
    $check = getimagesize($_FILES["avatar"]["tmp_name"]);
    if($check !== false) {
        if($avatar_path != "img/default_candidate_avatar.png"){
            unlink($avatar_path);
        }
        $avatar_path = "uploads/candidate_avatars/";
        $avatar_path =  $avatar_path . $candidate_id . '_' . time() . '_' . basename( $_FILES['avatar']['name']);
        move_uploaded_file($_FILES['avatar']['tmp_name'], "$avatar_path");
    }

    mysqli_query($mysqli,"UPDATE candidates SET candidate_avatar = '$avatar_path', candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $event_description = "Candidate $candidate_id avatar modifed.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Edit Candidate Avatar', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");
    
    $_SESSION['alert_message'] = "Candidate Avatar Updated";
    
    header("Location: $current_url");

}

if(isset($_POST['snapshot_candidate'])){
    $candidate_id = intval($_POST['candidate_id']);
    $avatar_path = $_POST['current_avatar_path'];
    $current_url = $_POST['current_url'];
    $img = $_POST['image'];

    $folderPath = "uploads/candidate_avatars/";
  
    $image_parts = explode(";base64,", $img);
    $image_type_aux = explode("image/", $image_parts[0]);
    $image_type = $image_type_aux[1];
  
    $image_base64 = base64_decode($image_parts[1]);
    $fileName = "$candidate_id.jpg";
  
    $file = $folderPath . $fileName;
    file_put_contents($file, $image_base64);
  
    print_r($fileName);

    mysqli_query($mysqli,"UPDATE candidates SET candidate_avatar = '$folderPath$fileName', candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $event_description = "Candidate $candidate_id got photo snapped.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Edit Candidate Avatar', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");
    
    $_SESSION['alert_message'] = "Candidate Photo Updated";
    
    header("Location: $current_url");

}


if(isset($_POST['add_company'])){
    $company_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_name']));
    $company_address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_address']));
    $company_city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_city']));
    $company_state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_state']));
    $company_zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_zip']));

    mysqli_query($mysqli,"INSERT INTO companies SET company_name = '$company_name', company_address = '$company_address', company_city = '$company_city', company_state = '$company_state', company_zip = '$company_zip', company_created_at = UNIX_TIMESTAMP()");
    
    $company_id = mysqli_insert_id($mysqli);

    $event_description = "Company <a href=''company.php?company_id=$company_id''>$company_name</a> created.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Company', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    mkdir("uploads/company_files/$company_id");

    $_SESSION['alert_message'] = "Company Added";

    header("Location: companies.php");
}

if(isset($_POST['edit_company'])){
    $company_id = intval($_POST['company_id']);
    $company_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_name']));
    $company_address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_address']));
    $company_city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_city']));
    $company_state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_state']));
    $company_zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_zip']));

    mysqli_query($mysqli,"UPDATE companies SET company_name = '$company_name', company_address = '$company_address', company_city = '$company_city', company_state = '$company_state', company_zip = '$company_zip', company_updated_at = UNIX_TIMESTAMP() WHERE company_id = $company_id");
    
    $event_description = "Company <a href=''company.php?company_id=$company_id''>$company_name</a> modified.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Edit Company', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Company Added";
    
    header('Location: companies.php');
}

if(isset($_POST['add_location'])){
    $location_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_name']));
    $location_address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_address']));
    $location_city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_city']));
    $location_state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_state']));
    $location_zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_zip']));
    $location_timezone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_timezone']));

    mysqli_query($mysqli,"INSERT INTO locations SET location_name = '$location_name', location_address = '$location_address', location_city = '$location_city', location_state = '$location_state', location_zip = '$location_zip', location_timezone = '$location_timezone', location_created_at = UNIX_TIMESTAMP()");

    $event_description = "Location $location_name created.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Location', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");
    
    $_SESSION['alert_message'] = "Location Added";
    
    header("Location: admin.php?tab=locations");
}

if(isset($_POST['edit_location'])){
    $location_id = intval($_POST['location_id']);
    $location_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_name']));
    $location_address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_address']));
    $location_city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_city']));
    $location_state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_state']));
    $location_zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_zip']));
    $location_timezone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['location_timezone']));

    mysqli_query($mysqli,"UPDATE locations SET location_name = '$location_name', location_address = '$location_address', location_city = '$location_city', location_state = '$location_state', location_zip = '$location_zip', location_timezone = '$location_timezone' WHERE location_id = $location_id");

    $event_description = "Location $location_name modified.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Edit Location', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Location Edited";
    
    header("Location: admin.php?tab=locations");
}

if(isset($_POST['add_contact'])){
    $company_id = intval($_POST['company_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $title = strip_tags(mysqli_real_escape_string($mysqli,$_POST['title']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));

    mysqli_query($mysqli,"INSERT INTO contacts SET contact_name = '$name', contact_title = '$title', contact_phone = '$phone', contact_email = '$email', contact_created_at = UNIX_TIMESTAMP(), company_id = $company_id");
    
    $sql = mysqli_query($mysqli,"SELECT company_name FROM companies WHERE company_id = $company_id");
    $row = mysqli_fetch_array($sql);

    $company_name = $row['company_name'];

    $event_description = "Contact $name created for Company <a href=''company.php?company_id=$company_id''>$company_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Company Contact', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Contact Added";
    
    header("Location: company.php?company_id=$company_id&tab=contacts");
}

if(isset($_POST['edit_contact'])){
    $contact_id = intval($_POST['contact_id']);
    $company_id = intval($_POST['company_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $title = strip_tags(mysqli_real_escape_string($mysqli,$_POST['title']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $email = strip_tags(mysqli_real_escape_string($mysqli,$_POST['email']));
    $company_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_name']));

    mysqli_query($mysqli,"UPDATE contacts SET contact_name = '$name', contact_title = '$title', contact_phone = '$phone', contact_email = '$email' WHERE contact_id = $contact_id");
    
    $event_description = "Contact $name edited for company <a href=''company.php?company_id=$company_id''>$company_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Edit Company Contact', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Contact edited";
    
    header("Location: company.php?company_id=$company_id&tab=contacts");
}

if(isset($_GET['delete_contact'])){
    $contact_id = intval($_GET['delete_contact']);
    $company_id = intval($_GET['company_id']);
    
    $event_description = "Contact Deleted";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Delete Candidate File', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    mysqli_query($mysqli,"DELETE FROM contacts WHERE contact_id = $contact_id");

    $_SESSION['alert_message'] = "Contact deleted";
    
    header("Location: company.php?company_id=$company_id&tab=contacts");
  
}


if(isset($_POST['add_company_note'])){
    $company_id = intval($_POST['company_id']);
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));

    mysqli_query($mysqli,"INSERT INTO notes SET note = '$note', note_created_at = UNIX_TIMESTAMP(), note_created_by = $session_user_id, company_id = $company_id");
    
    $sql = mysqli_query($mysqli,"SELECT company_name FROM companies WHERE company_id = $company_id");
    $row = mysqli_fetch_array($sql);

    $company_name = $row['company_name'];

    $event_description = "Note created for Company <a href=''company.php?company_id=$company_id&tab=notes''>$company_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Company Note', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Note Added";
    
    header("Location: company.php?company_id=$company_id&tab=notes");
}

if(isset($_POST['edit_company_note'])){
    $note_id = intval($_POST['note_id']);
    $company_id = intval($_POST['company_id']);
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));
    $company_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_name']));

    mysqli_query($mysqli,"UPDATE notes SET note = '$note' WHERE note_id = $note_id");

    $event_description = "Note edited for company <a href=''company.php?company_id=$company_id&tab=notes''>$company_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Edit Company Note', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Note Edited";
    
    header("Location: company.php?company_id=$company_id&tab=notes");
}

if(isset($_GET['delete_company_note'])){
    $note_id = intval($_GET['delete_company_note']);
    $company_id = intval($_GET['company_id']);

    $sql = mysqli_query($mysqli,"SELECT company_name FROM companies WHERE company_id = $company_id");
    $row = mysqli_fetch_array($sql);
                     
    $company_name = $row['company_name'];

    $event_description = "Note deleted for company <a href=''company.php?company_id=$company_id&tab=notes''>$company_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Delete Company Note', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    mysqli_query($mysqli,"DELETE FROM notes WHERE note_id = $note_id");

    $_SESSION['alert_message'] = "Note Deleted";
    
    header("Location: company.php?company_id=$company_id&tab=notes");
  
}

if(isset($_POST['upload_company_file'])){
    $company_id = intval($_POST['company_id']);

    if(!empty($_FILES['file'])){
        $path = "uploads/company_files/$company_id/";
        $path = $path . basename( $_FILES['file']['name']);
        $file_name = basename($path);
        move_uploaded_file($_FILES['file']['tmp_name'], $path);
    }

    mysqli_query($mysqli,"INSERT INTO files SET file_name = '$file_name', file_location = '$path', uploaded_at = UNIX_TIMESTAMP(), uploaded_by = $session_user_id, company_id = $company_id");

    $sql = mysqli_query($mysqli,"SELECT company_name FROM companies WHERE company_id = $company_id");
    $row = mysqli_fetch_array($sql);

    $company_name = $row['company_name'];

    $event_description = "File <a href=''$path''>$file_name</a> uploaded for company <a href=''company.php?company_id=$company_id&tab=files''>$company_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Company File', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "File Uploaded";
    
    header("Location: company.php?company_id=$company_id&tab=files");
}

if(isset($_GET['delete_company_file'])){
    $file_id = intval($_GET['delete_company_file']);

    $sql = mysqli_query($mysqli,"SELECT company_id, file_location FROM files WHERE file_id = $file_id");
    $row = mysqli_fetch_array($sql);
     
    $company_id = $row['company_id'];                 
    $file_location = $row['file_location'];
    $file_name = basename("$file_location");
    

    $sql = mysqli_query($mysqli,"SELECT company_name FROM companies WHERE company_id = $company_id");
    $row = mysqli_fetch_array($sql);
                     
    $company_name = $row['company_name'];


    $event_description = "File $file_name deleted for company <a href=''company.php?company_id=$company_id&tab=files''>$company_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Delete Company File', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    mysqli_query($mysqli,"DELETE FROM files WHERE file_id = $file_id");

    unlink($file_location);

    $_SESSION['alert_message'] = "File Deleted";
    
    header("Location: company.php?company_id=$company_id&tab=files");
  
}

if(isset($_POST['add_job'])){
    $company_id = intval($_POST['company_id']);
    $job_type = strip_tags(mysqli_escape_string($mysqli,$_POST['job_type']));
    $job_title = strip_tags(mysqli_real_escape_string($mysqli,$_POST['job_title']));
    $job_openings = intval($_POST['job_openings']);
    $job_description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['job_description']));

    mysqli_query($mysqli,"INSERT INTO jobs SET job_title = '$job_title', job_type = '$job_type', job_openings = $job_openings, job_description = '$job_description', job_created_at = UNIX_TIMESTAMP(), job_created_by = $session_user_id, company_id = $company_id");

    $sql = mysqli_query($mysqli,"SELECT company_name FROM companies WHERE company_id = $company_id");
    $row = mysqli_fetch_array($sql);

    $company_name = $row['company_name'];
    $event_description = "Job $job_title created for company <a href=''company.php?company_id=$company_id&tab=jobs''>$company_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Job', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Job Added";
    
    header("Location: jobs.php");
}

if(isset($_POST['edit_job'])){
    $job_id = intval($_POST['job_id']);
    $company_id = intval($_POST['company_id']);
    $job_type = strip_tags(mysqli_real_escape_string($mysqli,$_POST['job_type']));
    $job_title = strip_tags(mysqli_real_escape_string($mysqli,$_POST['job_title']));
    $job_openings = intval($_POST['job_openings']);
    $job_description = strip_tags(mysqli_real_escape_string($mysqli,$_POST['job_description']));

    mysqli_query($mysqli,"UPDATE jobs SET job_type = '$job_type', job_title = '$job_title', job_openings = $job_openings, job_description = '$job_description', company_id = $company_id WHERE job_id = $job_id");

    $sql = mysqli_query($mysqli,"SELECT company_name FROM companies WHERE company_id = $company_id");
    $row = mysqli_fetch_array($sql);

    $company_name = $row['company_name'];

    $event_description = "Job <a href=''edit_job.php?job_id=$job_id''>$job_title</a> edited for company <a href=''company.php?company_id=$company_id&tab=jobs''>$company_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Edit Job', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Job Edited";
    
    header("Location: jobs.php");
}

if(isset($_GET['delete_job'])){
    $job_id = intval($_GET['delete_job']);

    mysqli_query($mysqli,"DELETE FROM jobs WHERE job_id = $job_id");

    $event_description = "Job ID $job_id Deleted.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Delete Job', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Job Deleted";
    
    header("Location: jobs.php");
}

if(isset($_POST['add_education'])){
    $candidate_id = intval($_POST['candidate_id']);
    $education_type = strip_tags(mysqli_real_escape_string($mysqli,$_POST['education_type']));
    $education_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['education_name']));
    $address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['address']));
    $city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['city']));
    $state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['state']));
    $zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['zip']));
    $date_from = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date_from']));
    $date_to = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date_to']));
    $graduate = strip_tags(mysqli_real_escape_string($mysqli,$_POST['graduate']));
    $major = strip_tags(mysqli_real_escape_string($mysqli,$_POST['major']));

    mysqli_query($mysqli,"INSERT INTO candidate_education SET education_type = '$education_type', education_name = '$education_name', education_address = '$address', education_city = '$city', education_state = '$state', education_zip = '$zip', education_date_from = '$date_from', education_date_to = '$date_to', graduate = '$graduate', major = '$major', candidate_id = $candidate_id");
    
    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $event_description = "Added education $education_name $education_type to candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=education''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Education', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Education Added";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=education");
}

if(isset($_GET['delete_education'])){
    $education_id = intval($_GET['delete_education']);
    $candidate_id = intval($_GET['candidate_id']);

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $sql = mysqli_query($mysqli,"SELECT * FROM candidate_education WHERE education_id = $education_id");
    $row = mysqli_fetch_array($sql);

    $education_type = $row['education_type'];
    $education_name = $row['education_name'];

    $event_description = "Deleted education $education_name $education_type from candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=education''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Delete Education', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    mysqli_query($mysqli,"DELETE FROM candidate_education WHERE education_id = $education_id");

    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $_SESSION['alert_message'] = "Education Deleted";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=education");
  
}

if(isset($_POST['add_employment'])){
    $candidate_id = intval($_POST['candidate_id']);
    $company = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company']));
    $address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['address']));
    $city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['city']));
    $state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['state']));
    $zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['zip']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);
    $supervisor = strip_tags(mysqli_real_escape_string($mysqli,$_POST['supervisor']));
    $job_title = strip_tags(mysqli_real_escape_string($mysqli,$_POST['job_title']));
    $starting_salary = strip_tags(mysqli_real_escape_string($mysqli,$_POST['starting_salary']));
    $ending_salary = strip_tags(mysqli_real_escape_string($mysqli,$_POST['ending_salary']));
    $responsibilities = strip_tags(mysqli_real_escape_string($mysqli,$_POST['responsibilities']));
    $date_from = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date_from']));
    $date_to = strip_tags(mysqli_real_escape_string($mysqli,$_POST['date_to']));
    $reason_for_leave = strip_tags(mysqli_real_escape_string($mysqli,$_POST['reason_for_leave']));
    $allow_contact = strip_tags(mysqli_real_escape_string($mysqli,$_POST['allow_contact']));

    mysqli_query($mysqli,"INSERT INTO candidate_employment SET employment_company = '$company', employment_address = '$address', employment_city = '$city', employment_state = '$state', employment_zip = '$zip', employment_phone = '$phone', employment_supervisor = '$supervisor', employment_job_title = '$job_title', employment_starting_salary = '$starting_salary', employment_ending_salary = '$ending_salary', employment_responsibilities = '$responsibilities', employment_date_from = '$date_from', employment_date_to = '$date_to', employment_reason_for_leave = '$reason_for_leave', employment_allow_contact = '$allow_contact', candidate_id = $candidate_id");
    
    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $event_description = "Added employer $company to candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=employment''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Employer', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");


    $_SESSION['alert_message'] = "Employment Added";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=employment");
}

if(isset($_GET['delete_employment'])){
    $employment_id = intval($_GET['delete_employment']);
    $candidate_id = intval($_GET['candidate_id']);

    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $sql = mysqli_query($mysqli,"SELECT employment_company FROM candidate_employment WHERE employment_id = $employment_id");
    $row = mysqli_fetch_array($sql);

    $company = $row['employment_company'];

    $event_description = "Deleted employer $company from candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=employment''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Delete Employer', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    mysqli_query($mysqli,"DELETE FROM candidate_employment WHERE employment_id = $employment_id");

    $_SESSION['alert_message'] = "Employment Deleted";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=employment");
  
}

if(isset($_POST['add_reference'])){
    $candidate_id = intval($_POST['candidate_id']);
    $name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['name']));
    $relationship = strip_tags(mysqli_real_escape_string($mysqli,$_POST['relationship']));
    $address = strip_tags(mysqli_real_escape_string($mysqli,$_POST['address']));
    $city = strip_tags(mysqli_real_escape_string($mysqli,$_POST['city']));
    $state = strip_tags(mysqli_real_escape_string($mysqli,$_POST['state']));
    $zip = strip_tags(mysqli_real_escape_string($mysqli,$_POST['zip']));
    $company = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company']));
    $phone = strip_tags(mysqli_real_escape_string($mysqli,$_POST['phone']));
    $phone = preg_replace("/[^0-9]/", '',$phone);

    mysqli_query($mysqli,"INSERT INTO candidate_references SET reference_name = '$name', reference_relationship = '$relationship', reference_address = '$address', reference_city = '$city', reference_state = '$state', reference_zip = '$zip', reference_company = '$company', reference_phone = '$phone', candidate_id = $candidate_id");
    
    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $event_description = "Added reference $name to candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=references''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Reference', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Reference Added";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=references");
}

if(isset($_GET['delete_reference'])){
    $reference_id = intval($_GET['delete_reference']);
    $candidate_id = intval($_GET['candidate_id']);

    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $sql = mysqli_query($mysqli,"SELECT reference_name FROM candidate_references WHERE reference_id = $reference_id");
    $row = mysqli_fetch_array($sql);

    $reference_name = $row['reference_name'];

    $event_description = "Deleted reference $reference_name from candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=references''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Delete Reference', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    mysqli_query($mysqli,"DELETE FROM candidate_references WHERE reference_id = $reference_id");

    $_SESSION['alert_message'] = "Reference Deleted";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=references");
  
}

if(isset($_POST['hire_candidate'])){
    $candidate_id = intval($_POST['candidate_id']);
    $job_id = intval($_POST['job_id']);
    $company_id = intval($_POST['company_id']);
    $start_date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['start_date']));
    $start_time = strip_tags(mysqli_real_escape_string($mysqli,$_POST['start_time']));
    $start_date_time = $start_date . $start_time;
    $start_date_time_conv = strtotime($start_date_time);
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));
    $first_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['first_name']));
    $last_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['last_name']));
    $company_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_name']));
    $job_openings = intval($_POST['job_openings']);
    $job_title = strip_tags(mysqli_real_escape_string($mysqli,$_POST['job_title']));
    $new_job_openings = $job_openings - 1;

    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    mysqli_query($mysqli,"INSERT INTO candidate_work_history SET work_history_job = '$job_title', hired_date = UNIX_TIMESTAMP(), start_date = '$start_date_time_conv', user_id = $session_user_id, company_id = $company_id, candidate_id = $candidate_id");

    mysqli_query($mysqli,"INSERT INTO candidate_notes SET candidate_note = '$note', note_created_at = UNIX_TIMESTAMP(), note_created_by = $session_user_id, candidate_id = $candidate_id");

    mysqli_query($mysqli,"UPDATE candidates SET current_status = 'Hired - Followup', candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    mysqli_query($mysqli,"UPDATE jobs SET job_openings = $new_job_openings WHERE job_id = $job_id");

    $event_description = "Hired <a href=''candidate.php?candidate_id=$candidate_id&tab=work_history''>$first_name $last_name</a> for $job_title at the company <a href=''company.php?company_id=$company_id&tab=history''>$company_name</a>. The number of available positions changed from $job_openings to $new_job_openings";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Hired Candidate', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Candidate Hired";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=work_history");
}

if(isset($_POST['upload_candidate_file'])){
    $candidate_id = intval($_POST['candidate_id']);

    if(!empty($_FILES['file'])){
        $path = "uploads/candidate_files/$candidate_id/";
        $path = $path . basename( $_FILES['file']['name']);
        $file_name = basename($path);
        if(move_uploaded_file($_FILES['file']['tmp_name'], $path)) {
          $_SESSION['alert_message'] = "The file ".  basename( $_FILES['file']['name']). 
          " has been uploaded";
        } else{
            $_SESSION['alert_message'] = "There was an error uploading the file, please try again!";
        }
    }

    mysqli_query($mysqli,"INSERT INTO files SET file_location = '$path', uploaded_at = UNIX_TIMESTAMP(), uploaded_by = $session_user_id, candidate_id = $candidate_id");
    
    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $event_description = "File <a href=''$path''>$file_name</a> uploaded for candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=files''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Candidate File', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    header("Location: candidate.php?candidate_id=$candidate_id&tab=files");
}

if(isset($_GET['delete_candidate_file'])){
    $file_id = intval($_GET['delete_candidate_file']);

    $sql = mysqli_query($mysqli,"SELECT * FROM files WHERE file_id = $file_id");
    $row = mysqli_fetch_array($sql);
     
    $candidate_id = $row['candidate_id'];                 
    $file_location = $row['file_location'];
    $file_name = basename("$file_location");
    
    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);
                     
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $event_description = "File $file_name deleted for candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=files''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Delete Candidate File', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    mysqli_query($mysqli,"DELETE FROM files WHERE file_id = $file_id");

    unlink($file_location);

    $_SESSION['alert_message'] = "Note Deleted";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=files");
  
}

if(isset($_POST['add_candidate_note'])){
    $candidate_id = intval($_POST['candidate_id']);
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));

    mysqli_query($mysqli,"INSERT INTO notes SET note = '$note', note_created_at = UNIX_TIMESTAMP(), note_created_by = $session_user_id, candidate_id = $candidate_id");
    
    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    
    $event_description = "Note added to candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=notes''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Candidate Note', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Note Added.";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=notes");
}

if(isset($_POST['edit_candidate_note'])){
    $note_id = intval($_POST['note_id']);
    $candidate_id = intval($_POST['candidate_id']);
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));
    $first_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['first_name']));
    $last_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['last_name']));
    
    mysqli_query($mysqli,"UPDATE notes SET note = '$note' WHERE note_id = $note_id");
    
    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");
    
    $event_description = "Note edited for candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=notes''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Edit Candidate Note', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Note Edited";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=notes");
}

if(isset($_GET['delete_candidate_note'])){
    $note_id = intval($_GET['delete_candidate_note']);
    $candidate_id = intval($_GET['candidate_id']);

    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $event_description = "Deleted note from candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=notes''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Delete Candidate Note', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    mysqli_query($mysqli,"DELETE FROM notes WHERE note_id = $note_id");

    $_SESSION['alert_message'] = "Note Deleted";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=notes");
}

if(isset($_GET['interview_candidate'])){
    $candidate_id = intval($_GET['interview_candidate']);

    mysqli_query($mysqli,"UPDATE candidates SET current_status = 'Interviewing', candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $_SESSION['alert_message'] = "Candidate is now being Interviewed";
    
    header("Location: candidate.php?candidate_id=$candidate_id");
  
}

if(isset($_POST['followup_candidate'])){
    $work_history_id = intval($_POST['work_history_id']);
    $candidate_id = intval($_POST['candidate_id']);
    $showed_up = intval($_POST['showed_up']);
    $company_id = intval($_POST['company_id']);
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));
    $first_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['first_name']));
    $last_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['last_name']));
    $company_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_name']));
    $work_history_job = strip_tags(mysqli_real_escape_string($mysqli,$_POST['work_history_job']));
    if($showed_up == 1){
        $status = "Do Not Hire";
    }else{
        $status = "Placed";
    }

    mysqli_query($mysqli,"UPDATE candidates SET current_status = '$status', candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    mysqli_query($mysqli,"UPDATE candidate_work_history SET showed_up = $showed_up WHERE work_history_id = $work_history_id");

    mysqli_query($mysqli,"INSERT INTO candidate_notes SET candidate_note = '$note', note_created_at = UNIX_TIMESTAMP(), note_created_by = $session_user_id, candidate_id = $candidate_id");

    $event_description = "Candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=work_history''>$first_name $last_name</a> status changed to $status on followup for company <a href=''company.php?company_id=$company_id&tab=history''>$company_name</a> job $work_history_job.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Candidate Status Change', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Candidate status changed to $status";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=work_history");
  
}

if(isset($_POST['modify_work_history'])){
    $work_history_id = intval($_POST['work_history_id']);
    $candidate_id = intval($_POST['candidate_id']);
    $company_id = intval($_POST['company_id']);
    $note = strip_tags(mysqli_real_escape_string($mysqli,$_POST['note']));
    $first_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['first_name']));
    $last_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['last_name']));
    $company_name = strip_tags(mysqli_real_escape_string($mysqli,$_POST['company_name']));
    $work_history_job = strip_tags(mysqli_real_escape_string($mysqli,$_POST['work_history_job']));
    $start_date = strip_tags(mysqli_real_escape_string($mysqli,$_POST['start_date']));
    $start_time = strip_tags(mysqli_real_escape_string($mysqli,$_POST['start_time']));
    $start_date_time = $start_date . $start_time;
    $start_date_time_conv = strtotime($start_date_time);

    mysqli_query($mysqli,"UPDATE candidate_work_history SET start_date = '$start_date_time_conv' WHERE work_history_id = $work_history_id");

    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    mysqli_query($mysqli,"INSERT INTO candidate_notes SET candidate_note = '$note', note_created_at = UNIX_TIMESTAMP(), note_created_by = $session_user_id, candidate_id = $candidate_id");

    $event_description = "Candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=work_history''>$first_name $last_name</a> start time for job at company <a href=''company.php?company_id=$company_id&tab=history''>$company_name</a> job $work_history_job changed to $start_date_time_conv.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Candidate Status Change', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Candidate status changed to $status";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=work_history");
  
}

if(isset($_GET['inactive_candidate'])){
    $candidate_id = intval($_GET['inactive_candidate']);

    mysqli_query($mysqli,"UPDATE candidates SET current_status = 'Inactive', candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $_SESSION['alert_message'] = "Candidate marked inactive";
    
    header("Location: candidate.php?candidate_id=$candidate_id&tab=work_history");
  
}

if(isset($_GET['do_not_hire_candidate'])){
    $candidate_id= intval($_GET['do_not_hire_candidate']);

    mysqli_query($mysqli,"UPDATE candidates SET current_status = 'Do Not Hire', candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $event_description = "Candidate <a href=''candidate.php?candidate_id=$candidate_id''>$candidate_id</a> marked Do Not Hire!";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Candidate Status Change', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    $_SESSION['alert_message'] = "Candidate marked Do Not Hire";
    
    header("Location: candidate.php?candidate_id=$candidate_id");
  
}

if(isset($_GET['add_kbs_form'])){
    $candidate_id = intval($_GET['candidate_id']);
    $todays_date = date('m-d-Y',time());
    $sql = mysqli_query($mysqli,"SELECT * FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $address = $row['address'];
    $city = $row['city'];
    $state = $row['state'];
    $zip = $row['zip'];
    $phone = $row['phone'];
    if(strlen($phone)>2){ $phone = substr($row['phone'],0,3)."-".substr($row['phone'],3,3)."-".substr($row['phone'],6,4);}
    $social_security = $row['social_security'];

    $sql = mysqli_query($mysqli,"SELECT * FROM candidate_emergency_contacts WHERE candidate_id = $candidate_id LIMIT 1");
    $row = mysqli_fetch_array($sql);
    $emergency_contact_name = $row['emergency_contact_name'];
    $emergency_contact_relationship = $row['emergency_contact_relationship'];
    $emergency_contact_phone = $row['emergency_contact_phone'];
    if(strlen($emergency_contact_phone)>2){ 
        $emergency_contact_phone = substr($row['emergency_contact_phone'],0,3)."-".substr($row['emergency_contact_phone'],3,3)."-".substr($row['emergency_contact_phone'],6,4);
    }
    $unix_time = time();
    
    //Set the Content Type
    header('Content-type: image/png');

    // Create Image From Existing File
    $image = imagecreatefrompng("uploads/candidate_files/$candidate_id/kbs-1-candidate-signed.png");
    $image_2 = imagecreatefrompng("uploads/candidate_files/$candidate_id/kbs-5-candidate-signed.png");

    // Allocate A Color For The Text
    $black = imagecolorallocate($image, 0, 0, 0);
    $black_2 = imagecolorallocate($image_2, 0, 0, 0);
    

    // Set Path to Font File and Font Size
    putenv('GDFONTPATH=' . realpath('.'));
    $font = 'font.ttf';
    $font_size = '25';

    // Print Text On Image
    imagettftext($image, $font_size, 0, 405, 725, $black, $font, "$first_name $last_name");
    imagettftext($image, $font_size, 0, 600, 1200, $black, $font, $social_security);
    imagettftext($image, $font_size, 0, 600, 1275, $black, $font, "$address, $city, $state, $zip");
    imagettftext($image, $font_size, 0, 405, 1355, $black, $font, $phone);

    imagettftext($image, $font_size, 0, 205, 1510, $black, $font, $emergency_contact_name);
    imagettftext($image, $font_size, 0, 1400, 1510, $black, $font, $emergency_contact_relationship);
    imagettftext($image, $font_size, 0, 400, 1585, $black, $font, $emergency_contact_phone);

    imagettftext($image_2, $font_size, 0, 110, 1745, $black_2, $font, "$session_first_name $session_last_name");
    imagettftext($image_2, $font_size, 0, 110, 2025, $black_2, $font, "$todays_date");

    //Sends Image to File
    $save = "uploads/candidate_files/$candidate_id/kbs-1-candidate-signed.png";
    imagepng($image, $save, 0, NULL);

    $save = "uploads/candidate_files/$candidate_id/kbs-5-candidate-signed-employer-signed.png";
    imagepng($image_2, $save, 0, NULL);

    // Send Image to Browser
    //imagepng($image);

    shell_exec("convert $config_www_path/uploads/candidate_files/$candidate_id/kbs-1-candidate-signed.png $config_www_path/uploads/candidate_files/$candidate_id/kbs-2-candidate-signed.png $config_www_path/uploads/candidate_files/$candidate_id/kbs-3-candidate-signed.png $config_www_path/uploads/candidate_files/$candidate_id/kbs-4-candidate-signed.png $config_www_path/uploads/candidate_files/$candidate_id/kbs-5-candidate-signed-employer-signed.png $config_www_path/uploads/candidate_files/$candidate_id/KBS-Application_$candidate_id_$todays_date_2.pdf");

    $path = "uploads/candidate_files/$candidate_id/KBS-Application_$candidate_id_$todays_date_2.pdf";

    unlink("uploads/candidate_files/$candidate_id/kbs-1-candidate-signed.png");
    unlink("uploads/candidate_files/$candidate_id/kbs-2-candidate-signed.png");
    unlink("uploads/candidate_files/$candidate_id/kbs-3-candidate-signed.png");
    unlink("uploads/candidate_files/$candidate_id/kbs-4-candidate-signed.png");
    unlink("uploads/candidate_files/$candidate_id/kbs-5-candidate-signed.png");
    unlink("uploads/candidate_files/$candidate_id/kbs-5-candidate-signed-employer-signed.png");

    mysqli_query($mysqli,"INSERT INTO files SET file_location = '$path', uploaded_at = UNIX_TIMESTAMP(), uploaded_by = $session_user_id, candidate_id = $candidate_id");
    
    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $event_description = "File <a href=''$path''>$file_name</a> uploaded for candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=files''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Candidate File', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    header("Location: candidate.php?candidate_id=$candidate_id&tab=files");

    // Clear Memory
    imagedestroy($image);

}

if(isset($_POST['add_w4'])){
    $candidate_id = intval($_POST['candidate_id']);
    $first_date_of_employment = strip_tags($_POST['first_date_of_employment']);
    $ein = strip_tags($_POST['ein']);
    $todays_date = date('m-d-Y',time());
    
    //Set the Content Type
    header('Content-type: image/png');

    // Create Image From Existing File
    $image = imagecreatefrompng("uploads/candidate_files/$candidate_id/w4-candidate-filled.png");

    // Allocate A Color For The Text
    $black = imagecolorallocate($image, 0, 0, 0);

    // Set Path to Font File and Font Size
    putenv('GDFONTPATH=' . realpath('.'));
    $font = 'font.ttf';
    $font_size = '25';

    // Print Text On Image

    imagettftext($image, $font_size, 0, 1085, 2095, $black, $font, $first_date_of_employment);
    imagettftext($image, $font_size, 0, 1310, 2095, $black, $font, $ein);

    //Sends Image to File
    $save = "uploads/candidate_files/$candidate_id/w4-candidate-filled.png";
    imagepng($image, $save, 0, NULL);

    // Send Image to Browser
    //imagepng($image);

    shell_exec("convert $config_www_path/uploads/candidate_files/$candidate_id/w4-candidate-filled.png $config_www_path/uploads/candidate_files/$candidate_id/w4_$candidate_id_$todays_date_2.pdf");

    $path = "uploads/candidate_files/$candidate_id/w4_$candidate_id_$todays_date_2.pdf";

    //unlink("uploads/candidate_files/$candidate_id/w4-candidate-filled.png");

    mysqli_query($mysqli,"INSERT INTO files SET file_location = '$path', uploaded_at = UNIX_TIMESTAMP(), uploaded_by = $session_user_id, candidate_id = $candidate_id");
    
    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $event_description = "File <a href=''$path''>$file_name</a> uploaded for candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=files''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Candidate File', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    header("Location: candidate.php?candidate_id=$candidate_id&tab=files");

    // Clear Memory
    imagedestroy($image);

}

if(isset($_POST['add_i9'])){
    $i9_list_a_document_title = strip_tags($_POST['i9_list_a_document_title']);
    if(empty($i9_list_a_document_title)){
        $i9_list_a_document_title = "N/A";
    }
    $i9_list_a_issuing_authority = strip_tags($_POST['i9_list_a_issuing_authority']);
    if(empty($i9_list_a_issuing_authority)){
        $i9_list_a_issuing_authority = "N/A";
    }
    $i9_list_a_document_number = strip_tags($_POST['i9_list_a_document_number']);
    if(empty($i9_list_a_document_number)){
        $i9_list_a_document_number = "N/A";
    }
    if(empty($_POST['i9_list_a_expiration_date'])){
        $i9_list_a_expiration_date = "N/A";
    }else{
        $i9_list_a_expiration_date = date('m-d-Y',strtotime(strip_tags($_POST['i9_list_a_expiration_date'])));
    }
    $i9_list_b_document_title = strip_tags($_POST['i9_list_b_document_title']);
    if(empty($i9_list_b_document_title)){
        $i9_list_b_document_title = "N/A";
    }
    $i9_list_b_issuing_authority = strip_tags($_POST['i9_list_b_issuing_authority']);
    if(empty($i9_list_b_issuing_authority)){
        $i9_list_b_issuing_authority = "N/A";
    }
    $i9_list_b_document_number = strip_tags($_POST['i9_list_b_document_number']);
    if(empty($i9_list_b_document_number)){
        $i9_list_b_document_number = "N/A";
    }
    if(empty($_POST['i9_list_b_expiration_date'])){
        $i9_list_b_expiration_date = "N/A";
    }else{
        $i9_list_b_expiration_date = date('m-d-Y',strtotime(strip_tags($_POST['i9_list_b_expiration_date'])));
    }
    $i9_list_c_document_title = strip_tags($_POST['i9_list_c_document_title']);
    if(empty($i9_list_c_document_title)){
        $i9_list_c_document_title = "N/A";
    }
    $i9_list_c_issuing_authority = strip_tags($_POST['i9_list_c_issuing_authority']);
    if(empty($i9_list_c_issuing_authority)){
        $i9_list_c_issuing_authority = "N/A";
    }
    $i9_list_c_document_number = strip_tags($_POST['i9_list_c_document_number']);
    if(empty($i9_list_c_document_number)){
        $i9_list_c_document_number = "N/A";
    }
    if(empty($_POST['i9_list_c_expiration_date'])){
        $i9_list_c_expiration_date = "N/A";
    }else{
        $i9_list_c_expiration_date = date('m-d-Y',strtotime(strip_tags($_POST['i9_list_c_expiration_date'])));
    }
    $i9_first_day_of_employment = date('m-d-Y',strtotime(strip_tags($_POST['i9_first_day_of_employment'])));
    $i9_employer_title = strip_tags($_POST['i9_employer_title']);
    $i9_employer_last_name = strip_tags($_POST['i9_employer_last_name']);
    $i9_employer_first_name = strip_tags($_POST['i9_employer_first_name']);
    $i9_employer_business_name = strip_tags($_POST['i9_employer_business_name']);
    $i9_employer_business_address = strip_tags($_POST['i9_employer_business_address']);
    $i9_employer_business_city_or_town = strip_tags($_POST['i9_employer_business_city_or_town']);
    $i9_employer_business_state = strip_tags($_POST['i9_employer_business_state']);
    $i9_employer_business_zip_code = strip_tags($_POST['i9_employer_business_zip_code']);
    $signature = $_POST['signature_image_base64'];
    $unix_time = time(); 

    $candidate_id = intval($_POST['candidate_id']);
    $todays_date = date('m-d-Y',time());
    $todays_date_2 = date('Y-m-d',time());
    
    //Set the Content Type
    header('Content-type: image/png');

    // Create Image From Existing File
    $image = imagecreatefrompng("uploads/candidate_files/$candidate_id/i9-2-candidate-filled.png");
    $sig = imagecreatefrompng($signature);
   
    $sig = imagescale($sig,380,45);

    // Allocate A Color For The Text
    $white = imagecolorallocate($image, 255, 255, 255);
    $grey = imagecolorallocate($image, 128, 128, 128);
    $black = imagecolorallocate($image, 0, 0, 0);
    

    // Set Path to Font File and Font Size
    putenv('GDFONTPATH=' . realpath('.'));
    $font = 'font.ttf';
    $font_size = '18';

    // Print Text On Image
    imagettftext($image, $font_size, 0, 110, 540, $black, $font, $i9_list_a_document_title);
    imagettftext($image, $font_size, 0, 110, 605, $black, $font, $i9_list_a_issuing_authority);
    imagettftext($image, $font_size, 0, 110, 665, $black, $font, $i9_list_a_document_number);
    imagettftext($image, $font_size, 0, 110, 730, $black, $font, $i9_list_a_expiration_date);

    imagettftext($image, $font_size, 0, 625, 540, $black, $font, $i9_list_b_document_title);
    imagettftext($image, $font_size, 0, 625, 605, $black, $font, $i9_list_b_issuing_authority);
    imagettftext($image, $font_size, 0, 625, 665, $black, $font, $i9_list_b_document_number);
    imagettftext($image, $font_size, 0, 625, 730, $black, $font, $i9_list_b_expiration_date);

    imagettftext($image, $font_size, 0, 1130, 540, $black, $font, $i9_list_c_document_title);
    imagettftext($image, $font_size, 0, 1130, 605, $black, $font, $i9_list_c_issuing_authority);
    imagettftext($image, $font_size, 0, 1130, 665, $black, $font, $i9_list_c_document_number);
    imagettftext($image, $font_size, 0, 1130, 730, $black, $font, $i9_list_c_expiration_date);

    imagettftext($image, $font_size, 0, 795, 1385, $black, $font, $i9_first_day_of_employment);

    imagecopy($image, $sig, 110, 1430 , 0, 0, 380, 45);
    imagettftext($image, $font_size, 0, 750, 1470, $black, $font, $todays_date);
    imagettftext($image, $font_size, 0, 1065, 1470, $black, $font, $i9_employer_title);

    imagettftext($image, $font_size, 0, 110, 1545, $black, $font, $i9_employer_last_name);
    imagettftext($image, $font_size, 0, 620, 1545, $black, $font, $i9_employer_first_name);
    imagettftext($image, $font_size, 0, 1130, 1545, $black, $font, $i9_employer_business_name);

    imagettftext($image, $font_size, 0, 110, 1620, $black, $font, $i9_employer_business_address);
    imagettftext($image, $font_size, 0, 875, 1620, $black, $font, $i9_employer_business_city_or_town);
    imagettftext($image, $font_size, 0, 1245, 1620, $black, $font, $i9_employer_business_state);
    imagettftext($image, $font_size, 0, 1350, 1620, $black, $font, $i9_employer_business_zip_code);


    // Send Image to Browser
    //imagepng($image);

    //Sends Image to File
    $save = "uploads/candidate_files/$candidate_id/i9-2-employer-filled.png";
    imagepng($image, $save, 0, NULL);

    //Convert PNGs and Combine them to PDF
    shell_exec("convert $config_www_path/uploads/candidate_files/$candidate_id/i9-1-candidate-filled.png $config_www_path/uploads/candidate_files/$candidate_id/i9-2-employer-filled.png $config_www_path/uploads/candidate_files/$candidate_id/i9_$candidate_id_$todays_date_2.pdf");

    //Delete PNGs after they been converted to PDF
    unlink("uploads/candidate_files/$candidate_id/i9-1-candidate-filled.png");
    unlink("uploads/candidate_files/$candidate_id/i9-2-candidate-filled.png");
    unlink("uploads/candidate_files/$candidate_id/i9-2-employer-filled.png");

    $path = "uploads/candidate_files/$candidate_id/i9_$candidate_id_$todays_date_2.pdf";

    mysqli_query($mysqli,"INSERT INTO files SET file_location = '$path', uploaded_at = UNIX_TIMESTAMP(), uploaded_by = $session_user_id, candidate_id = $candidate_id");
    
    mysqli_query($mysqli,"UPDATE candidates SET candidate_updated_at = UNIX_TIMESTAMP(), candidate_updated_by = $session_user_id WHERE candidate_id = $candidate_id");

    $sql = mysqli_query($mysqli,"SELECT first_name, last_name FROM candidates WHERE candidate_id = $candidate_id");
    $row = mysqli_fetch_array($sql);

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];

    $event_description = "File <a href=''$path''>$file_name</a> uploaded for candidate <a href=''candidate.php?candidate_id=$candidate_id&tab=files''>$first_name $last_name</a>.";

    mysqli_query($mysqli,"INSERT INTO events SET event_type = 'Add Candidate File', event_description = '$event_description', event_created_at = UNIX_TIMESTAMP(), user_id = $session_user_id");

    header("Location: candidate.php?candidate_id=$candidate_id&tab=files");

    // Clear Memory
    //imagedestroy($image);

}

if(isset($_POST['add_ticket'])){
    $ticket_subject = strip_tags(mysqli_real_escape_string($mysqli,$_POST['ticket_subject']));
    $ticket_body = strip_tags(mysqli_real_escape_string($mysqli,$_POST['ticket_body']));

    mysqli_query($mysqli,"INSERT INTO tickets SET ticket_status = 'New', ticket_subject = '$ticket_subject', ticket_body = '$ticket_body', ticket_created_at = UNIX_TIMESTAMP(), ticket_created_by = $session_user_id");

    $_SESSION['alert_message'] = "Ticket Created";
    
    header("Location: tickets.php");

}
if(isset($_GET['hello'])){
    $hello = $_GET['hello'];
    echo $hello;
}

?>	