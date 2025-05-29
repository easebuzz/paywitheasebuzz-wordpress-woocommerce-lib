<?php

// Load WordPress configuration
if (file_exists('../../../wp-config.php')) {
    include('../../../wp-config.php');
}

try {
    // Check if the request method is POST, otherwise return 405 Method Not Allowed
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo "Error: Method Not Allowed. Only POST requests are allowed.";
        exit;
    }

    // Establish a secure database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Read and parse incoming request body
    $body = file_get_contents('php://input');
    parse_str(urldecode($body), $object);

    $SALT = "XXXXXXXXXXXX";

    $is_hash_matched = _getReverseHashKey($object, $SALT);

    if ($is_hash_matched) {

        $order_id = intval($object['udf1']);
        $status = $object['status'];
        $easepayid = $conn->real_escape_string($object['easepayid']);
        $txnid = $conn->real_escape_string($object['txnid']);
        $bank_ref_num = $conn->real_escape_string($object['bank_ref_num']);
        $mode = $conn->real_escape_string($object['mode']);

        $stmt = $conn->prepare("SELECT status FROM {$wpdb->prefix}wc_orders WHERE id = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error . " SQL: SELECT status FROM {$wpdb->prefix}wc_orders WHERE id = ?");
        }
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $current_status = $row['status'];

            if (in_array($current_status, ['wc-processing', 'wc-completed'])) {
                http_response_code(200);
                echo "Order status already updated in the database.";
                
            } else if (in_array($current_status, ['wc-failed', 'wc-pending', 'wc-cancelled'])) {

                $existing_status_sql = "SELECT status FROM {$wpdb->prefix}wc_orders WHERE id = ?";
                $stmt = $conn->prepare($existing_status_sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error . " SQL: $existing_status_sql");
                }
                $stmt->bind_param('i', $order_id);
                $stmt->execute();
                $stmt->bind_result($existing_status);
                $stmt->fetch();
                $stmt->close();

                if($status === 'usercancelled') {
                    $new_status = 'wc-cancelled';
                } else {
                    $new_status = ($status === 'success') ? 'wc-processing' : 'wc-failed';
                }

                if ($existing_status === $new_status) {
                    echo "Order status is already updated.";
                } else {         
                    $sql = "UPDATE {$wpdb->prefix}wc_orders SET status = '". $new_status ."' WHERE id = '". $order_id ."'";
                    if (!$conn->query($sql)) {
                        http_response_code(400);
                        echo "Error: Order status update failed in wc_orders.";
                        exit;
                    }
                    update_post_meta($order_id, '_transaction_id', $easepayid);
                    echo "Order status updated from webhook.";
                }

                $comment = "Easebuzz payment: $status<br>Easbeuzz ID: $easepayid<br>($txnid)<br>Bank Ref: $bank_ref_num($mode)";
                $stmt = $conn->prepare("INSERT INTO {$wpdb->prefix}comments (comment_post_ID, comment_author, comment_date, comment_date_gmt, comment_content, comment_karma, comment_approved, comment_agent, comment_type, comment_parent, user_id) VALUES (?, 'Easebuzz - Webhook', NOW(), NOW(), ?, 0, 1, 'woocommerce', 'order_note', 0, 0)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error . " SQL: INSERT INTO {$wpdb->prefix}comments (comment_post_ID, comment_author, comment_date, comment_date_gmt, comment_content, comment_karma, comment_approved, comment_agent, comment_type, comment_parent, user_id) VALUES (?, 'Easebuzz - Webhook', NOW(), NOW(), ?, 0, 1, 'woocommerce', 'order_note', 0, 0)");
                }
                $stmt->bind_param('is', $order_id, $comment);
                $stmt->execute();

            } else {
                http_response_code(400);
                echo "Order status not changed/updated from webhook";
            }
        } else {
            http_response_code(400);
            echo "Error: Order not found or multiple results returned.";
        }
    } else {
        http_response_code(400);
        echo "Error: Hash key not matched.";
    }
} catch (Exception $e) {
    http_response_code(400);
    echo "Error: " . $e->getMessage();
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

function _getReverseHashKey($response_array, $s_key){
    $reverse_hash_sequence = "udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key";

    $reverse_hash = "";
    $reverse_hash_sequence_array = explode('|', $reverse_hash_sequence);
    $reverse_hash .= $s_key . '|' . $response_array['status'];

    foreach ($reverse_hash_sequence_array as $value) {
        $reverse_hash .= '|';
        $reverse_hash .= isset($response_array[$value]) ? $response_array[$value] : '';
    }

    $reverse_hash_key = strtolower(hash('sha512', $reverse_hash));

    if ($reverse_hash_key === $response_array['hash']) {
        return true;
    }
}
