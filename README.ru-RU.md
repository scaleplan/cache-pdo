# Db

#### Установка

``
composer reqire scaleplan/cache-pdo
``
<br>

#### Описание

Db представляет собой класс-обертку для взаимодествия PHP-приложений с СУБД PostgreSQL и MySQL. Позволяет прозрачно взаимодействовать с любой из этих СУБД не вникая в различия взаимодейтвия PHP с этими системами для разработчика - работа с обоими СУБД будет одинакова с точки зрения программирования.

Класс поддерживает подготовленные выражения. 
Кроме того есть дополнительная функциональность для реализации концепции параллельного выполнения запросов внутри одного подключени к базе данных и методы для реализации асинхронного выполнения пакетов запросов.

<br>

[Документация класса](docs_ru)
