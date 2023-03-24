<?php
if (!defined("ABSPATH")) {
    die("you can't be here");
}

add_shortcode("contact", "show_contact_form");

add_action("rest_api_init", "create_rest_endpoint");

add_action("init", "create_submissions_page");

add_action("add_meta_boxes", "create_meta_box");

add_filter("manage_submission_posts_columns", "custom_submission_columns");

add_action("manage_submission_posts_custom_column", "fill_submission_columns", 10, 2);

add_action("admin_init", "setup_search");

add_action("wp_enqueue_scripts", "enqueue_custom_script");

function enqueue_custom_script()
{
    wp_enqueue_style("contact-form-plugin", MY_PLUGIN_URL . "/assets/css/contact-plugin.css");
}

function setup_search()
{
    global $typenow;

    if ($typenow == "submission") {
        add_filter("posts_search", "submission_search_override", 10, 2);
    }
}

function submission_search_override($search, $query)
{
    global $wpdb;
    if ($query->is_main_query() && !empty($query->query["s"])) {
        $sql = "
            or exists (
                select * from {$wpdb->postmeta} where post_id={$wpdb->posts}.ID
                and meta_key in ('name', 'email', 'phone')
                and meta_value like %s
            )
        ";
        $like = "%" . $wpdb->esc_like($query->query["s"]) . "%";
        $search = preg_replace(
            "#\({$wpdb->posts}.post_title like [^)]+\)\K#",
            $wpdb->prepare($sql, $like),
            $search
        );
    }

    return $search;
}

function fill_submission_columns($column, $post_id)
{
    switch ($column) {
        case "name":
            echo esc_html(get_post_meta($post_id, "name", true));
            break;
        case "email":
            echo esc_html(get_post_meta($post_id, "email", true));
            break;
        case "phone":
            echo esc_html(get_post_meta($post_id, "phone", true));
            break;
        case "message":
            echo esc_html(get_post_meta($post_id, "message", true));
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
    echo "<li><strong>Name: </strong><br />" . esc_html(get_post_meta(get_the_ID(), "name", true)) . "</li>";
    echo "<li><strong>Email: </strong><br />" . esc_html(get_post_meta(get_the_ID(), "email", true)) . "</li>";
    echo "<li><strong>Phone: </strong><br />" . esc_html(get_post_meta(get_the_ID(), "phone", true)) . "</li>";
    echo "<li><strong>Message: </strong><br />" . esc_html(get_post_meta(get_the_ID(), "message", true)) . "</li>";
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
        "menu_position" => 30,
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

    $name = $params["name"];

    $headers = [];

    $sender_email = get_bloginfo("admin_email");
    $sender_name = get_bloginfo("name");
    $recipient_email = get_plugin_options("contact_plugin_recipients");
    $confirmation_message = get_plugin_options("contact_plugin_message");
    if ($confirmation_message) {
        $confirmation_message = str_replace("{name}", $name, $confirmation_message);
    }

    if (!$recipient_email) {
        $recipient_email = $sender_email;
    }

    $headers[] = "From: {{$sender_name}} <{$recipient_email}>";
    $headers[] = "Reply-to: <{$params['name']}> <{$params['email']}> ";
    $headers[] = "Content-Type: text/html";

    $subject = "New enquiry from {$params['name']}";

    $message = "";
    $message .= "<h1>Message has been sent from {$params['name']}</h1> <br /> <br />";

    $postArr = [
        "post_title" => $params["name"],
        "post_type" => "submission",
        "post_status" => "publish"
    ];
    $post_id = wp_insert_post($postArr);


    foreach ($params as $label => $value) {
        $message .= "<strong>" . ucfirst($label) . '</strong>: ' . $value . "<br />";
        add_post_meta($post_id, $label,  sanitize_text_field($value));
    }

    if (!$confirmation_message) {
        $confirmation_message = "Message sent";
    }

    wp_mail($recipient_email, $subject, $message, $headers);

    return new WP_REST_Response($confirmation_message, 200);
}
