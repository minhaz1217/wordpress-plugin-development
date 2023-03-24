<?php

add_shortcode("contact", "show_contact_form");

add_action("rest_api_init", "create_rest_endpoint");

add_action("init", "create_submissions_page");

function create_submissions_page()
{
    $args = [
        "public" => true,
        "has_archive" => true,
        "labels" => [
            "name" => "Submissions",
            "singular_name" => "Submission"
        ],
        // "capability_type" => "post",
        "capabilities" => ["create_posts" => "do_not_allow"]
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

    foreach ($params as $label => $value) {
        $message .= "<strong>" . ucfirst($label) . '</strong>: ' . $value . "<br />";
    }

    wp_mail($sender_email, $subject, $message, $headers);

    return new WP_REST_Response("Message sent", 200);
}
