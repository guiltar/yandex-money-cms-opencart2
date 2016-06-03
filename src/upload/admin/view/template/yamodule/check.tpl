<?php echo $header; ?><?php echo $column_left;
$is_good = true;
?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <h3>Проверка настроек</h3>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-10 col-sm-offset-0">
                    <div class="form-group">
                        Проверяется готовность модуля для приема платежей через Яндекс.Кассу.
                    </div>
            </div>
        </div>
        <?php foreach ($listTests as $clsTest){ ?>
            <div class="row">
                <div class="col-sm-11 col-sm-offset-1">
                    <div class="form-group">
                        <label class="col-sm-3"><?php echo $$clsTest->getTitle();?></label>
                        <div class="col-sm-9">
                            <?php if($$clsTest->done){ ?> <div class="col-sm-12"><?php echo $$clsTest->getResult(); ?></div><?php } ?>
                            <div class="col-sm-12"><?php echo $$clsTest->getWarnHtml(); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php if ($$clsTest->done === false) $is_good = false; ?>
        <?php } ?>
        <div class="row">
            <div class="col-sm-11 col-sm-offset-1">
                <div class="form-group">
                    <label class="col-sm-3">Результат</label>
                    <div class="col-sm-9">
                        <div class="col-sm-12">
                        <?php if ($is_good){ ?>
                        С настройками всё хорошо. Чтобы закончить проверку, сделайте тестовый платеж по инструкции от менеджера Яндекс.Кассы.
                        <?php }else{ ?>
                        Есть ошибки. Исправьте их по рекомендациям выше и повторите проверку.
                        <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo $footer; ?>
