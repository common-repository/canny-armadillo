<?php

class Canny_Armadillo
{
    public function init(): void
    {
        add_action('admin_menu', [$this, 'adminSetupMenu']);
        add_action('plugins_loaded', [$this, 'checkPluginVersion']);
        add_action('wp_enqueue_scripts', [$this, 'registerScripts']);
        add_action('wp_footer', [$this, 'datalayer']);
        add_action( 'init', [$this, 'setCookie']);
    }

    public function adminSetupMenu(): void
    {
        add_options_page('Canny Armadillo', 'Canny Armadillo', 'manage_options', 'canny-armadillo', [$this, 'adminInit']);
    }

    public function cannyArmadilloActivation()
    {
        if (!get_option("Canny_Armadillo_send_user_data")) {
            add_option("Canny_Armadillo_send_user_data", true);
        }
    }

    public function adminInit(): void
    {
        $this->handlePost();
        ?>
        <div class="wrap">
            <br><h1>Canny Armadillo</h1>

            <p>Enter your Armadillo Code below</p>
            <p>To find your Armadillo code go to your <a target="_blank" href="https://app.cannyarmadillo.com/">Canny Armadillo Dashboard</a> and go to your Organization Dashboard. Next click on the "Getting Started" tab. Look for something like: <code>&#60;script async src="https://example.cannyarmadillo.com/a/canny-armadillo.js"&#62;&#60;/script&#62;</code>. Of this you want <strong>example.cannyarmadillo.com</strong></p>
            <p>If you are using user impersonating (with <a target="_blank" href="https://wordpress.org/plugins/user-switching/">User Switching</a>, <a target="_blank" href="https://wordpress.org/plugins/view-admin-as/">View Admin As</a>, or <a target="_blank" href="https://wordpress.org/plugins/capability-manager-enhanced/">PublishPress Capabilities</a>) we disable The Armadillo while impersonating.</p>
            <form method="post">
                <?php wp_nonce_field( 'Canny_Armadillo_armadillo_action' ); ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th><label for="Canny_Armadillo_armadillo_code">Armadillo Code</label></th>
                        <td>
                            <input class="regular-text" type="text" id="Canny_Armadillo_armadillo_code" name="Canny_Armadillo_armadillo_code" value="<?php echo esc_html(get_option('Canny_Armadillo_armadillo_code')); ?>">
                            <p class="description" id="tagline-description">(ex: example.cannyarmadillo.com)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="Canny_Armadillo_send_user_data">Send User Info for logged in User</label></th>
                        <td>
                            <input class="regular-text" type="checkbox" id="Canny_Armadillo_send_user_data" name="Canny_Armadillo_send_user_data" value="1" <?php if(get_option('Canny_Armadillo_send_user_data') && get_option('Canny_Armadillo_send_user_data') === '1'): echo 'checked'; endif; ?>>
                            <p class="description" id="tagline-description">We don't include the Armadillo for Users who are administrator</p>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php submit_button('Save') ?>
            </form>

        </div>
        <?php
    }

    public function handlePost(): void
    {
        if (isset($_POST['Canny_Armadillo_armadillo_code'])) {
            check_admin_referer( 'Canny_Armadillo_armadillo_action' );
            $d = sanitize_text_field($_POST['Canny_Armadillo_armadillo_code']);
            if (!$d) {
                echo '<div class="updated error"><p>Enter A Valid Armadillo Code </p></div>';

                return;
            }

            $valid = preg_match("/([a-z\d-]{1,63}\.[a-z]{1,63}(\.[a-z]{2})?)$/i", $d) && preg_match('/.{1,255}/i', $d);
            if (!$valid) {
                echo '<div class="updated error"><p>Enter A Valid Armadillo Code </p></div>';

                return;
            }

            if (substr($d, 0, 8) !== 'https://' && substr($d, 0, 7) !== 'http://') {
                $d = 'https://' . $d;
            }

            $url = parse_url($d)['host'];
            if (isset(parse_url($d)['path'])) {
                $url .= parse_url($d)['path'];
            }

            if (isset(parse_url($d)['query'])) {
                $url .= '?' . parse_url($d)['query'];
            }

            $url = rtrim($url, '/');
            $check = count(explode('.', $url));
            if(strpos($url, '/')) {
                $url = substr($url, 0, strpos($url, '/'));
            }

            if ($check !== 3) {
                echo '<div class="updated error"><p>Enter A Valid Armadillo Code </p></div>';
            } else {

                if(isset($_POST['Canny_Armadillo_send_user_data'])) {
                    $sendData = sanitize_text_field($_POST['Canny_Armadillo_send_user_data']) === '1' ? true : false;
                } else {
                    $sendData = false;
                }

                update_option('Canny_Armadillo_armadillo_code', strtolower($url));
                update_option('Canny_Armadillo_send_user_data', $sendData);
                echo '<div class="updated notice is-dismissible"><p>Armadillo Code Updated</p></div>';
            }
        }
    }

    public function checkPluginVersion(): void
    {
        if (CANNY_ARMADILLO_PLUGIN !== get_option('Canny_Armadillo_plugin')) {
            $this->cannyArmadilloActivation();
            update_option('Canny_Armadillo_plugin', CANNY_ARMADILLO_PLUGIN);
        }
    }

    public function datalayer(): void
    {
        $user = wp_get_current_user();

        $sendData = get_option('Canny_Armadillo_send_user_data') && get_option('Canny_Armadillo_send_user_data') === '1';
        if($user && $user->id && !current_user_can('administrator') && $sendData):

            $name = trim($user->first_name . ' ' . $user->last_name);
            ?>
            <script>
                let armadilloDataLayer = {
                    user: {
                        'id' : '<?php echo esc_html($user->id); ?>',
                        <?php if($name): ?>
                        'name' : '<?php echo esc_html($name); ?>',
                        <?php endif; ?>
                        'email' : '<?php echo esc_html(strtolower($user->user_email)); ?>',
                    },
                }
            </script>

        <?php endif;
    }

    public function setCookie()
    {
        $url = sanitize_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
        $host = parse_url($url)['host'] ?? null;
        $host = sanitize_url($host);

        if(isset($_COOKIE["canny_armadillo"])) {
            $arr_cookie_options = array (
                'expires' => time() + (60 * 60 * 24 * 365),
                'path' => '/',
                'domain' => '.' . $host,
                'secure' => true,
                'httponly' => false,
                'samesite' => 'Strict',
            );

            setcookie("canny_armadillo", sanitize_text_field($_COOKIE["canny_armadillo"]), $arr_cookie_options);
        }
    }

    public function registerScripts(): void
    {
        $is_impersonating = $this->isUserImpersonated();
        if (get_option('Canny_Armadillo_armadillo_code') && !current_user_can('administrator') && !$is_impersonating) {
            // wp_get_current_user
            wp_register_script('canny-armadillo-armadillo', 'https://' . get_option('Canny_Armadillo_armadillo_code') . '/a/canny-armadillo.js', '', CANNY_ARMADILLO_PLUGIN, true);
            wp_enqueue_script('canny-armadillo-armadillo');
        }
    }

    public function isUserImpersonated(): bool
    {
        $is_impersonated = false;

        // User Switching
        // https://wordpress.org/plugins/user-switching/
        if (method_exists('user_switching', 'get_old_user')) {
            try {
                $old_user = user_switching::get_old_user();
                if ($old_user) {
                    $is_impersonated = true;
                }
            } catch (Throwable $e) {
                // Do nothing
            }
        }

        // View Admin As
        // https://wordpress.org/plugins/view-admin-as/
        if (method_exists('VAA_View_Admin_As', 'get_instance')) {
            try {
                $store = view_admin_as()->store();
                if ($store) {
                    $views = $store->get_view(null);

                    if($views) {
                        $is_impersonated = true;
                    }
                }
            } catch (Throwable $e) {
                // Do nothing
            }
        }

        // PublishPress Capabilities
        // https://wordpress.org/plugins/capability-manager-enhanced/
        if (method_exists('PP_Capabilities_Test_User', 'testerAuth')) {
            try {
                $auth_key = 'ppc_test_user_tester_' . COOKIEHASH;
                if (isset($_COOKIE[$auth_key]) && !empty($_COOKIE[$auth_key])) {
                    $is_impersonated = true;
                }
            } catch (Throwable $e) {
                // Do nothing
            }
        }

        return $is_impersonated;
    }
}
