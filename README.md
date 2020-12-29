# Convert Composer Lock
This code will convert your `composer.lock` to `composer.json`
## Attention
This code is in beta and may not work correctly
## Example usage
```php
$convert = new ConvertComposerLock($path_to_composer_lock);
file_put_contents($path_to_new_composer_json, json_encode([
    'require' => $convert->getRequire(),
    'require-dev' => $convert->getRequireDev(),
]));
```
## License
![GitHub](https://img.shields.io/github/license/Geriler/convert-composer-lock)
## Questions?
Write to me on [telegram](https://t.me/karl_stein) and I will try to help you
