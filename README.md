# drupalsesmailer
A high performance mail system for Drupal using Amazon Simple Email Service (SES). Using this module it is possible
to send thousand or more emails per minute from your Drupal site.

Instead of sending each and every email directly when it is created this module writes all emails to the database
and sends them in batches on cron run. This way the full potential of the AWS SES API is used and concurrent sending
of emails is possible.

In addition the module provides a way to send  priority emails such as registration, password reset or account cancellation
immediately, without delaying them.

You may style your emails using templates and all css is inlined using the emogrifier library. In addition a plain text
alternative is generated. You may use a template per mail key.    


## REQUIREMENTS
Of course you need an Amazon AWS account.
In addition this module relies on:
* Mail System
* Ultimate Cron
* Composer Manager (to include the Emogrifier Library)


## INSTALLATION
Install Mail System, Ultimate Cron and Composer Manager on your Drupal site.
Enable this module. Run "composer update".
Include the configuration options (see below) in your settings.php.

## CONFIGURATION
Include the following configuration options in your settings.php file:

required:
```php
$conf['ses_mail']['awskey'] = 'your key';
$conf['ses_mail']['awssecret'] = 'your secret';
$conf['ses_mail']['awsregion'] = 'aws region';
$conf['ses_mail']['mails_per_cron_run'] = 1000; --> how many mails should be processed during a single cron run
$conf['ses_mail']['concurrency'] = 50; --> always stay below your sending limit per second
$conf['ses_mail']['seconds_between_batches'] = 0; --> you may add a some "sleep" between batch sends to ease your sending limit per second
$conf['ses_mail']['send_direct'] = array('register_no_approval_required', 'register_admin_created', 'register_pending_approval', 'password_reset', 'status_activated', 'status_blocked', 'cancel_confirm', 'status_canceled'); --> add mail keys that should be sent directly
```

optional you may include:
```php
$conf['ses_mail']['delete_successful_sends'] = 86400; --> this will clear the html and text column after 24 hours seconds to save space on your db server
$conf['ses_mail']['clear_failed_sends'] = 2592000; --> this will clear the complete mail row in the database after 30 days
```


## CUSTOMIZATION
You may create a sesmail.tpl.php template in your mail theme's template folder and a corresponding css file.

### Example:
```
template: sites/all/themes/yourtheme/templates/sesmail.tpl.php
css:  sites/all/themes/yourtheme/css/sesmail.css
```

In addition it is possible to define template files and corresponding css files for individual mail keys.
To use this you have to implement hook_mail_alter() and set $message['themehook'] = mailthemehook.

### Example:
```php
// hook_theme()
function yourmodule_theme(){
    $config = 'mailthemehook' => array(
          'template' => 'mailtemplate',
          'variables' => array(
              'message' => '',
          ),
          'path' => drupal_get_path('module', 'ses_mail'),
          'mail theme' => TRUE,
    );
    return $config;
}

// hook_mail_alter()
function yourmodule_mail_alter(&$message){
    if($message['key'] == 'your_message_key'){
        $message['themehook'] = 'mailthemehook';
    }
}
```

and create the following files:
```
sites/all/themes/yourtheme/templates/mailtemplate.tpl.php
sites/all/themes/yourtheme/css/mailtemplate.css
```


## TROUBLESHOOTING
If the Emogrifier Library is not found after install run "composer dumpautoload" in your composer directory.

You can test send emails using http://yoursite.com/ses_mail/test

Current maintainers:
* Mathias Müller (muellm)

This project has been sponsored by:
* FRAGNEBENAN
