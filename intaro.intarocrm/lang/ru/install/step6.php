<?php
$MESS ['INTAROCRM_INFO'] = '
<h2>Дальнейшие действия<h2>
<p>
    Если вы произвели выгрузку заказов на шаге 3, то эти заказы уже доступны в вашей CRM и
    через некоторое время по этим заказам будет подготовлены аналитические отчеты в Панели KPI.
</p>
<p>
    Новые заказы будут отправляться агентом <span style="font-family: Courier New;">ICrmOrderActions::uploadOrdersAgent();</span>
    в retailCRM каждые 10 минут (интервал можно изменить в разделе <a href="/bitrix/admin/agent_list.php">Агенты</a>).
</p>
<p>
    Если вы выбрали опцию «Выгрузить каталог сейчас» на шаге 4, то ваш каталог уже загружается в retailCRM.
    Загрузка длится, как правило, не более 10 минут. Если вы не выбирали эту опцию, то генерацию файла с каталогом
    можно произвести экспортом «retailCRM» в разделе Магазин > Настройки > <a href="/bitrix/admin/cat_export_setup.php">Экспорт данных</a>.
    retailCRM проверяет и загружает данный файл с каталогом каждые 3 часа.
</p>
';
