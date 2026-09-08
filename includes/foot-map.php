<?php

declare(strict_types=1);

function renderFootMap(array $marks, bool $editable = false): void
{
    $zones = [
        ['id' => 'hallux', 'cx' => 78, 'cy' => 42, 'rx' => 11, 'ry' => 16],
        ['id' => 'd2', 'cx' => 98, 'cy' => 32, 'rx' => 8, 'ry' => 14],
        ['id' => 'd3', 'cx' => 114, 'cy' => 30, 'rx' => 7, 'ry' => 13],
        ['id' => 'd4', 'cx' => 128, 'cy' => 34, 'rx' => 7, 'ry' => 12],
        ['id' => 'd5', 'cx' => 140, 'cy' => 46, 'rx' => 7, 'ry' => 11],
        ['id' => 'antepe', 'cx' => 108, 'cy' => 88, 'rx' => 38, 'ry' => 28],
        ['id' => 'interdigital', 'cx' => 108, 'cy' => 58, 'rx' => 34, 'ry' => 10],
        ['id' => 'arco', 'cx' => 112, 'cy' => 140, 'rx' => 28, 'ry' => 32],
        ['id' => 'calcaneo', 'cx' => 118, 'cy' => 198, 'rx' => 26, 'ry' => 24],
    ];
    $marksByZone = [];
    foreach ($marks as $mark) {
        $key = ($mark['pe'] ?? '') . ':' . ($mark['zona'] ?? '');
        $marksByZone[$key][] = $mark;
    }
    ?>
    <div class="foot-map <?= $editable ? 'is-editable' : 'is-readonly' ?>" data-editable="<?= $editable ? '1' : '0' ?>">
        <div class="foot-canvas">
            <?php foreach (['esquerdo', 'direito'] as $pe): ?>
                <svg class="foot-svg" viewBox="0 0 200 240" data-pe="<?= e($pe) ?>" aria-label="Pé <?= e($pe) ?>">
                    <text x="100" y="228" text-anchor="middle" class="foot-label"><?= $pe === 'esquerdo' ? 'Pé esquerdo' : 'Pé direito' ?></text>
                    <ellipse class="foot-sole" cx="110" cy="130" rx="48" ry="78"/>
                    <?php foreach ($zones as $zone): ?>
                        <?php $key = $pe . ':' . $zone['id']; $hasMark = isset($marksByZone[$key]); ?>
                        <ellipse
                            class="foot-zone <?= $hasMark ? 'has-mark' : '' ?>"
                            data-zona="<?= e($zone['id']) ?>"
                            data-pe="<?= e($pe) ?>"
                            cx="<?= $zone['cx'] ?>"
                            cy="<?= $zone['cy'] ?>"
                            rx="<?= $zone['rx'] ?>"
                            ry="<?= $zone['ry'] ?>"
                        />
                    <?php endforeach; ?>
                </svg>
            <?php endforeach; ?>
        </div>
        <div class="foot-legend">
            <?php foreach (marcaTipos() as $tipo => $label): ?>
                <span class="mark-chip mark-<?= e($tipo) ?>"><?= e($label) ?></span>
            <?php endforeach; ?>
        </div>
        <ul class="foot-marks" id="footMarksList">
            <?php foreach ($marks as $index => $mark): ?>
                <li data-index="<?= $index ?>">
                    <span class="mark-chip mark-<?= e($mark['tipo'] ?? 'outro') ?>"><?= e(marcaTipos()[$mark['tipo'] ?? ''] ?? 'Marca') ?></span>
                    <?= e($mark['pe'] === 'direito' ? 'Direito' : 'Esquerdo') ?> · <?= e(zonaLabels()[$mark['zona'] ?? ''] ?? $mark['zona'] ?? '') ?>
                    <?php if (!empty($mark['observacao'])): ?> · <?= e($mark['observacao']) ?><?php endif; ?>
                    <?php if ($editable): ?><button type="button" class="mark-remove" data-index="<?= $index ?>" aria-label="Remover">&times;</button><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($editable): ?>
            <input type="hidden" name="marcas_json" id="marcasJson" value="<?= e(json_encode(array_values($marks), JSON_UNESCAPED_UNICODE) ?: '[]') ?>">
            <p class="muted foot-hint">Toque em uma região do pé para registrar o achado clínico.</p>
            <div class="foot-picker" id="footPicker" hidden>
                <strong id="footPickerTitle">Região</strong>
                <select class="form-select form-select-sm" id="footPickerTipo">
                    <?php foreach (marcaTipos() as $tipo => $label): ?>
                        <option value="<?= e($tipo) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="form-control form-control-sm" id="footPickerNota" placeholder="Observação">
                <div class="form-actions compact">
                    <button type="button" class="btn btn-light btn-sm" id="footPickerCancel">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" id="footPickerSave">Marcar</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
