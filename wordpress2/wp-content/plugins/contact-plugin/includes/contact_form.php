<?php

add_shortcode("contact", "show_contact_form");

add_action("rest_api_init", "create_rest_endpoint");

add_action("init", "create_submissions_page");

add_action("add_meta_boxes", "create_meta_box");

add_filter("manage_submission_posts_columns", "custom_submission_columns");

add_action("manage_submission_posts_custom_column", "fill_submission_columns", 10, 2);

function fill_submission_columns($column, $post_id)
{
    switch ($column) {
        case "name":
            echo get_post_meta($post_id, "name", true);
            break;
        case "email":
            echo get_post_meta($post_id, "email", true);
            break;
        case "phone":
            echo get_post_meta($post_id, "phone", true);
            break;
        case "message":
            echo get_post_meta($post_id, "message", true);
            break;
    }
}

function custom_submission_columns($columns)
{
    $columns = array(
        "cb" => $columns["cb"],
        "name" => __("Name", 'contact-plugin'),
        "email" => __("Email", 'contact-plugin'),
        "phone" => __("Phone", 'contact-plugin'),
        "message" => __("Message", 'contact-plugin'),
    );
    return $columns;
}

function create_meta_box()
{
    add_meta_box("custom_contact_form", "Submission", "display_submission", "submission");
}

function display_submission()
{
    $postMetas = get_post_meta(get_the_ID());

    echo "<ul>";
    echo "<li><strong>Name: </strong><br />" . get_post_meta(get_the_ID(), "name", true) . "</li>";
    echo "<li><strong>Email: </strong><br />" . get_post_meta(get_the_ID(), "email", true) . "</li>";
    echo "<li><strong>Phone: </strong><br />" . get_post_meta(get_the_ID(), "phone", true) . "</li>";
    echo "<li><strong>Message: </strong><br />" . get_post_meta(get_the_ID(), "message", true) . "</li>";
    echo "</ul>";

    // foreach ($postMetas as $key => $value) {
    //     echo "<li><strong>" . ucfirst($key) . "</strong><br />" . $value[0] . "</li>";
    // }

}

function create_submissions_page()
{
    $args = [
        "public" => true,
        "has_archive" => true,
        "labels" => [
            "name" => "Submissions",
            "singular_name" => "Submission"
        ],
        "supports" => false,
        "capability_type" => "post",
        "capabilities" => array(
            "create_posts" => false
        ),
        "map_meta_cap" => true

        // "supports" => ["custom-fields"],
        // "supports" => ["title", "editor",  "custom-fields"],
        // "capability_type" => "post",
        // "capabilities" => ["create_posts" => "do_not_allow"]
    ];
    register_post_type("submission", $args);
}

function show_contact_form()
{
    include MY_PLUGIN_PATH . '/includes/templates/contact-form.php';
}

function create_rest_endpoint()
{
    register_rest_route("v1/contact-form", "submit", array(
        "methods" => "POST",
        "callback" => "handle_enquiry"
    ));
}

function handle_enquiry($data)
{
    $params = $data->get_params();
    if (!wp_verify_nonce($params["_wpnonce"], "wp_rest")) {
        return new WP_REST_Response("Message not sent", 422);
    }

    unset($params["_wpnonce"]);
    unset($params["_wp_http_referer"]);

    $headers = [];

    $sender_email = get_bloginfo("admin_email");
    $sender_name = get_bloginfo("name");

    $headers[] = "From: {{$sender_name}} <{$sender_email}>";
    $headers[] = "Reply-to: <{$params['name']}> <{$params['email']}> ";
    $headers[] = "Content-Type: text/html";

    $subject = "New enquiry from {$params['name']}";

    $message = "";
    $message .= "<h1>Message has been sent from {$params['name']}</h1> <br /> <br />";

    $postArr = [
        "post_title" => $params["name"],
        "post_type" => "submission",
        "post_status" => "published"
    ];
    $post_id = wp_insert_post($postArr);


    foreach ($params as $label => $value) {
        $message .= "<strong>" . ucfirst($label) . '</strong>: ' . $value . "<br />";
        add_post_meta($post_id, $label, $value);
    }


    wp_mail($sender_email, $subject, $message, $headers);

    return new WP_REST_Response("Message sent", 200);
}
