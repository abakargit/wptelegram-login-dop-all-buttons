<?php defined('ABSPATH') || die();

add_action('wp_ajax_nopriv_telegram_auth', 'telegram_auth_callback');
add_action('wp_ajax_telegram_auth', 'telegram_auth_callback');

function telegram_auth_callback()
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || empty($data['id']) || empty($data['auth_date']) || empty($data['hash'])) {
        wp_send_json(['success' => false, 'message' => 'Error data']);
    }

    $options = get_option('wptelegram_login');
    $bot_token = $options['bot_token'] ?? '';

    if (!$bot_token) {
        wp_send_json(['success' => false, 'message' => 'Error config Telegram']);
    }
    
    $check_hash = $data['hash'];
    unset($data['hash']);
    ksort($data);

    $data_check_string = '';
    foreach ($data as $key => $value) {
        $data_check_string .= "$key=$value\n";
    }

    $secret_key = hash('sha256', $bot_token, true);
    $hash = hash_hmac('sha256', trim($data_check_string), $secret_key);

    // hash
    if (!hash_equals($hash, $check_hash)) {
        wp_send_json(['success' => false, 'message' => 'error data']);
    }

    $telegram_id = $data['id'];
    $telegram_username = sanitize_user($data['username'] ?? 'tg_' . $telegram_id);
    $first_name = $data['first_name'] ?? '';
    $last_name = $data['last_name'] ?? '';
    $full_name = trim($first_name . ' ' . $last_name);
    $photo_url = $data['photo_url'] ?? ''; 
    $email = ''; // default

    $user_query = new WP_User_Query([
        'meta_key' => 'telegram_id',
        'meta_value' => $telegram_id,
        'number' => 1
    ]);

    if (!empty($user_query->get_results())) {
        $user = $user_query->get_results()[0];
    } else {
        $user = get_user_by('login', $telegram_username);
    }

    if ($user) {
        $user_id = $user->ID;
        wp_set_auth_cookie($user_id, true);
    } else {
        // create user
        $password = wp_generate_password();
        $user_id = wp_create_user($telegram_username, $password, $email);
        if (is_wp_error($user_id)) {
            wp_send_json(['success' => false, 'message' => 'Error create user']);
        }

        // Add role user
        $user = new WP_User($user_id);
        $user->set_role('subscriber');
        wp_set_auth_cookie($user_id, true);
    }

    // Update colomn in wp_users for telegram_id and telegram_username
    global $wpdb;
    $wpdb->update(
        $wpdb->users,
        [
            'telegram_id' => $telegram_id,
            'telegram_username' => $telegram_username
        ],
        ['ID' => $user_id],
        ['%d', '%s'],
        ['%d']
    );

    // Update in wp_usermeta data
    update_user_meta($user_id, 'wptg_login_avatar', $photo_url);
    update_user_meta($user_id, 'first_name', $first_name);
    //update_user_meta($user_id, 'last_name', $last_name);
    update_user_meta($user_id, 'display_name', $full_name);
    update_user_meta($user_id, 'wptelegram_user_id', $telegram_id);
    update_user_meta($user_id, 'wptelegram_username', $telegram_username);

    wp_send_json(['success' => true, 'redirect_url' => home_url('/account')]);
}


// Replace avatar in Telegram-avatar
add_filter('get_avatar', function ($avatar, $id_or_email, $size, $default, $alt) {
    $user_id = 0;

    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif ($id_or_email instanceof WP_User) {
        $user_id = $id_or_email->ID;
    } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
        $user_id = (int) $id_or_email->user_id;
    }

    if ($user_id) {
        $telegram_avatar = get_user_meta($user_id, 'wptg_login_avatar', true);
        if ($telegram_avatar) {
            return "<img src='{$telegram_avatar}' width='{$size}' height='{$size}' class='avatar avatar-{$size} photo' alt='{$alt}' />";
        }
    }

    return $avatar;
}, 10, 5);



// get id telegram bot
function add_telegram_script() {
    $options = get_option('wptelegram_login');
    $bot_id = isset($options['bot_token']) ? explode(':', $options['bot_token'])[0] : '';

    wp_register_script('custom-telegram-auth', '', [], false, true);
    wp_enqueue_script('custom-telegram-auth');
    wp_add_inline_script('custom-telegram-auth', "window.TELEGRAM_BOT_ID = '{$bot_id}';");
}
add_action('wp_enqueue_scripts', 'add_telegram_script');