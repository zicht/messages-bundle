# ZichtMessagesBundle: manage your translatable messages in the database #

The ZichtMessagesBundle provides an Doctrine entity and admin screens for managing messages and have the database
provide translations for message keys. Additionally, some console tools are available for your convenience.

## Admin ##
The admin is based on the SonataAdminBundle.

## Console tools ##
Some utility console tools are available.

* `zicht:messages:add` adds a message to the database catalogue
* `zicht:messages:load` loads a translation file into the database catalogue
* `zicht:messages:flush` flushes Symfony's translation cache

