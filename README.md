# Создание кэша по дате изменения файла
**Disclaimer:** Module is not complete and not ready for use yet.

# Использование
```php
Cache::exec(array('path/to/file'),'somefn',array($arg1,$arg2)); //- Функция somefn выполнится если было изменение указанных файлов
Cache::exec(true,'somefn',array($arg1,$arg2)); //- Функция somefn выполняется всегда
Cache::exec(true,'somefn',array($arg1,$arg2),$data); //-Установка нового значения в кэше. Функция somefn не выполняется.
```
