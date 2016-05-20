# Создание кэша по дате изменения файла
**Disclaimer:** Module is not complete and not ready for use yet.

# Использование
```php
Cache::exec(array('path/to/file'), 'somefn', $fn, array($arg1, $arg2)); //- Функция somefn выполнится если было изменение указанных файлов
Cache::exec(true, 'somefn', $fn, array($arg1, $arg2)); //- Функция somefn выполняется всегда
Cache::exec(true, 'somefn', array($arg1, $arg2), $data); //-Установка нового значения в кэше. Функция somefn не выполняется.
```


Если ниодного файла в условии $cond не существует кэш будет сделан "навсегда", до тех пор пока не появится файл или до тех пор пока не будет очищен существующий кэш. Аргументы необязательны.
