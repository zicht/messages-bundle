# ZichtMessagesBundle: manage your translatable messages in the database #
The ZichtMessagesBundle provides an Doctrine entity and admin screens 
 for managing messages and have the database provide translations for 
 message keys. Additionally, some console tools are available for your 
 convenience.
 
## Admin ##
The admin is based on the SonataAdminBundle.

## Console tools ##
Some utility console tools are available.

* `zicht:messages:add` adds a message to the database catalogue
* `zicht:messages:load` loads a translation file into the database catalogue
* `zicht:messages:flush` flushes Symfony's translation cache

## Installing ##
* Make sure the following directory is present in your project: app/Resources/translations
* Add the db file for each domain-locale combination, for instance: message.en.db
* Add a translation via the CMS.
* Clear the cache with `php app/console cache:clear`
* All existing message states will be *unknown*, you need to update these once by running
  `zicht:messages:load --sync`

## Configure manual cache clear ##
Edit your `zicht_admin.yml` and add:

```yaml
zicht_admin:
    rc:
        messages:
            route: zicht_messages_rc_flush
            method: DELETE
            title: Clear translation cache
            button: Clear
```

## Message state ##
The messages-bundle maintains a state for each message, this state can
 be either *import*, *user*, or *unknown*.  You can configure z to 
 import messages on every deploy by adding the following:
  
```
tasks:
    deploy:
        post: 
            - @messages.load_files
```

To ensure that the message state is properly updated, add the following
to you z config:

```
messages:
    overwrite_compatibility: false
```
## Maintainer(s) ##
* Boudewijn Schoon <boudewijn@zicht.nl>
* Philip Bergman <philip@zicht.nl>
