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

## Automatic translation
Leverage the `zicht:message:translate` command with automatic translation through an API of choice.
Kickstart the usage by using the provided Google Translate API.

### Setup

`composer require google/cloud-translate`

### Configuration
Define the Google Translator as a service in your project. Your API key should be the private key from a service account (https://cloud.google.com/translate/docs/basic/setup-basic)

```yaml
parameters:
    env(GOOGLE_API_KEY): '%kernel.root_dir%/config/your-google-api-key.json'
```
```xml
<service id="Zicht\Bundle\MessagesBundle\Translator\GoogleTranslator">
    <argument key="$googleTranslateServiceAccount">%env(json:file:resolve:GOOGLE_API_KEY)%</argument>
</service>
```

Add a `CompilerPass` to register a `BatchTranslatorInterface` on the `MessageTranslator`:

```php
use Zicht\Bundle\MessagesBundle\Translator\GoogleTranslator;
use Zicht\Bundle\MessagesBundle\Translator\MessageTranslator;

/**
 * {@inheritDoc}
 */
public function process(ContainerBuilder $container)
{
    $container
        ->getDefinition(MessageTranslator::class)
        ->addMethodCall('setBatchTranslator', [new Reference(GoogleTranslator::class)]);
}
```

### Usage

In this example we have copied a `.nl.yaml` to a `es.yaml` and we are informing the command that the sourcelanguage is `nl` and the
targetlanguage should be `es`. As we have already renamed the file, only contents of this file will be rewritten.

```shell script
php bin/console zicht:message:translate /dir/to/project/translations/validators.es.(yaml|xlf) --source=nl --target=es
```

### Conditions

The targetlanguage (`--target=xx`) is required for `yaml` as it cannot be autodiscovered. 
For `xlf` we use the `target-language` attribute inside the file, but can be forced by using the target-option as well. 

Parameters in the translations are rewritten and not sent to the translation-api to prevent translating them. They should be in the format of `%param%`, `!param` or `{param}`.

If your file is in `xliff`, we only support `1.2`.

If your file is in `yaml`, and has hierarchical contents, this will be lost and the file will be rewritten with single lines containing the full path to your translation.

#### Before

```yaml
app:
   index:
        title: Abc
``` 
#### After

```yaml
app.index.title: Abc
```

## Maintainer(s) ##
* Boudewijn Schoon <boudewijn@zicht.nl>
