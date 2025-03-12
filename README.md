# Telegram Login Enhancement for [WP Telegram Login](https://github.com/wpsocio/wptelegram-login)

This script extends the functionality of the [WP Telegram Login](https://github.com/wpsocio/wptelegram-login) plugin by allowing Telegram authentication to be triggered from any button on your website.

## Features

- **Flexible Integration**: Attach Telegram login to any button by adding the class `.custom-telegram-login`.
- **Multiple Buttons Supported**: You can use multiple buttons on the same page for authentication.
- **Customizable**: If needed, you can change the class name, but remember to update it in the JavaScript accordingly.

## Installation

### 1. Add script in `functions.php`
Include the following line in your `functions.php` file to ensure the necessary script is loaded:

```php
require get_template_directory() . '/inc/telegram-hook.php';
```

### 2. Add script in `footer.php`
Place this script in your `footer.php` to enable Telegram login on your site:

```html
<script src="https://telegram.org/js/telegram-widget.js?1.11.7"></script>
<script>
   document.querySelectorAll(".custom-telegram-login").forEach(button => {
      button.addEventListener("click", function () {
         Telegram.Login.auth(
            {
               bot_id: window.TELEGRAM_BOT_ID, 
               request_access: true
            },
            function (user) {
               // Send data to WordPress AJAX
               fetch("/wp-admin/admin-ajax.php?action=telegram_auth", {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify(user)
               })
               .then(response => response.json())
               .then(data => {
                  if (data.success) {
                     window.location.href = data.redirect_url; // Redirect after successful login
                  } else {
                     alert("Error: " + data.message);
                  }
               })
               .catch(error => console.error("Request error:", error));
            }
         );
      });
   });
</script>
```

### 3. Add login button anywhere on your site
Use the `.custom-telegram-login` class on any button where you want to trigger Telegram login:

```html
<button class="custom-telegram-login">Login with Telegram</button>
```

If you decide to change the class name, make sure to update it in the JavaScript as well.

## Requirements  
- The [WP Telegram Login](https://github.com/wpsocio/wptelegram-login) plugin must be installed and configured.  
- Ensure `TELEGRAM BOT TOKEN` is correctly set before running the script.  
- Don't forget to link your website domain to the bot using the following command in [BotFather](https://t.me/BotFather):  

```html
/setdomain
yourdomain
```

## License

This extension follows the same licensing terms as the [WP Telegram Login](https://github.com/wpsocio/wptelegram-login) plugin.
