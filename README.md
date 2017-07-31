PHP Client Credentials with Microsoft Graph
=============================

This is a very simple application example that implements getting any user's calendar using 
Azure Active Directory as the authentication provide. This practice is applicable for services or deamon apps. 
More technical details you may find on 
[documentation](https://developer.microsoft.com/en-us/graph/docs/authorization/app_only). Keep in mind that you
can use only 1.0 end-points. [Should you use the v2.0 endpoint?](https://docs.microsoft.com/pl-pl/azure/active-directory/develop/active-directory-v2-limitations) 

Preconditions
------------
*  application created in Azure Active Directory,
*  granted permission (`Read calendars in all mailboxes`) by administrator for Microsoft Graph API 
(it's worth to mention that it must be `Application Permission`, not `Delegated Permission`),
*  prepared application id, [secret](https://auth0.com/docs/connections/enterprise/azure-active-directory#4-create-the-key), [tenant id](https://stackoverflow.com/questions/26384034/how-to-get-the-azure-account-tenant-id/41028320#41028320).


Quick start
------------

Application was tested with PHP 7.1. 

* install dependencies

`composer install`

* fill out a `config-sample.yml` file with your data

```
user_principal_name: my_username@company.com # app will fetch events for this guy
client_id: client_id
client_secret: client_secret
tenant_id: tenant_id
```

* rename the config file to `config.yml`

* launch a build-in web server 

`php -S 127.0.0.1:8000`

* visit localhost:8000 in your browser.
