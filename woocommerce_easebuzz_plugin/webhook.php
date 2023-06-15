<?php

    if(file_exists('../../../wp-config.php')){
        include('../../../wp-config.php');
    }

	$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	// Check connection
	if ($conn->connect_error) {
	  die("Connection failed: " . $conn->connect_error);
	}

    $body = file_get_contents('php://input');
    parse_str(urldecode($body), $result);
    foreach($result as $key => $value){
        $object[$key] = $value;
    } 
    $sql = "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = '".$object['udf1']."' and meta_key = '".'_order_key'."'";
    $res = $conn->query($sql);
    $txnid = "";
    if ($res->num_rows == 1) {
        while($row = $res->fetch_assoc()) {
            $txnid = $row['meta_value'];
        }
    }
    $sql = "SELECT post_status FROM {$wpdb->prefix}posts WHERE ID = '".$object['udf1']."' LIMIT 1";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        while($row = $result->fetch_assoc()) {
            if ('wc-processing' !== $row['status']) {  
                if($object['status'] == 'success' && $txnid == $object['txnid'] ){
                    $comment = "Easebuzz payment : ".$object['status']."<br>Easbeuzz ID: ".$object['easepayid']."<br>(". $object['txnid'].")<br>PG: ".$object['PG_TYPE']." <br>Bank Ref:".$object['bank_ref_num']."(".$object['mode'].")";
                    $order_id = intval($object['udf1']);
                    $sql = "insert into {$wpdb->prefix}comments( comment_post_ID, comment_author, comment_date, comment_date_gmt, comment_content, comment_karma, comment_approved, comment_agent, comment_type, comment_parent, user_id) values('$order_id', 'Easebuzz', NOW(),NOW(),'$comment', 0, '1', 'woocommerce', 'order_note', 0,0)";                    
                    $conn->query($sql);

                    $sql = "UPDATE {$wpdb->prefix}wc_order_stats SET status = 'wc-processing' where order_id = '".$object['udf1']."'";
                    $conn->query($sql);

                    $sql = "UPDATE {$wpdb->prefix}posts SET post_status = 'wc-processing' where ID = '".$object['udf1']."'";
                    $conn->query($sql);
                } else if($object['status'] == 'failure' && $txnid == $object['txnid'] ){
                    $comment = "Easebuzz payment : ".$object['status']."<br>Easbeuzz ID: ".$object['easepayid']."<br>(". $object['txnid'].")<br>PG: ".$object['PG_TYPE']." <br>Bank Ref:".$object['bank_ref_num']."(".$object['mode'].")";
                    $order_id = intval($object['udf1']);
                    $sql = "insert into {$wpdb->prefix}comments( comment_post_ID, comment_author, comment_date, comment_date_gmt, comment_content, comment_karma, comment_approved, comment_agent, comment_type, comment_parent, user_id) values('$order_id', 'Easebuzz', NOW(),NOW(),'$comment', 0, '1', 'woocommerce', 'order_note', 0,0)";                    
                    $conn->query($sql);

                    $sql = "UPDATE {$wpdb->prefix}wc_order_stats SET status = 'wc-failed' where order_id = '".$object['udf1']."'";
                    $conn->query($sql);

                    $sql = "UPDATE {$wpdb->prefix}posts SET post_status = 'wc-failed' where ID = '".$object['udf1']."'";
                    $conn->query($sql);

                } else if($object['status'] == 'userCancelled' && $txnid == $object['txnid'] ){
                    $comment = "Easebuzz payment : ".$object['status']."<br>Easbeuzz ID: ".$object['easepayid']."<br>(". $object['txnid'].")<br>PG: ".$object['PG_TYPE']." <br>Bank Ref:".$object['bank_ref_num']."(".$object['mode'].")";
                    $order_id = intval($object['udf1']);
                    $sql = "insert into {$wpdb->prefix}comments( comment_post_ID, comment_author, comment_date, comment_date_gmt, comment_content, comment_karma, comment_approved, comment_agent, comment_type, comment_parent, user_id) values('$order_id', 'Easebuzz', NOW(),NOW(),'$comment', 0, '1', 'woocommerce', 'order_note', 0,0)";                    
                    $conn->query($sql);

                    $sql = "UPDATE {$wpdb->prefix}wc_order_stats SET status = 'wc-failed' where order_id = '".$object['udf1']."'";
                    $conn->query($sql);

                    $sql = "UPDATE {$wpdb->prefix}posts SET post_status = 'wc-failed' where ID = '".$object['udf1']."'";
                    $conn->query($sql);
                } else{

                }
            }
        }
    } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
    }



?>

