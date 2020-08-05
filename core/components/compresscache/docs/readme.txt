
--------------------
Extra: CompressCache
--------------------
Version: 1.0.0
Author: SergeJ Solov'yov <sergej.soloyov@yandex.ru>

# modX-Compress-Cache
Extension that allows you to compress the file cache in CMS modX

Расширения для сжатия файлов кеша CMS modX

## Features
Provides a class cache provider that stores the cache in gzip compressed files

Предоставляет класс-провайдер кеша, хранящий кеш в файлах сжатых gzip

## Install and Settings
Install using the package installer. 
1. Clear the cache
2. As a provider of the cache to specify the class "cache.CompressFileCache"
This can be done in the settings in the core space of the caching section.
a) to compress all cache files, use the cache_handler parameter
b) to compress a specific type of cache files, create a parameter like cache_<type>_handler, for example, cache_resource_handler to compress the resource cache

To configure the compression level, use the compression_level parameter (core space, caching section)
Acceptable values from 0 to 9 or -1-use the default compression level of the zlib library

Для установки используйте менеджер пакетов
1. Очистите кеш
2. Укажите в качестве провайдера кеша класс "cache.CompressFileCache"
Для этого используйте параметры настроек пространства core секции Кеширование
a) Для сжатия всех файлов кеша используйте параметр cache_handler
b) Для сжатия файлов кеша конкретного типа создайте параметр вида cache_<тип>_handler, например, cache_resource_handler для сжатия кеша ресурсов

<a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/"><img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by-nc-sa/4.0/88x31.png" /></a><br />This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License</a>.
<br />Это произведение доступно по <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/deed.ru">лицензии Creative Commons «Attribution-NonCommercial-ShareAlike» («Атрибуция-Некоммерчески-СохранениеУсловий») 4.0 Всемирная</a>.