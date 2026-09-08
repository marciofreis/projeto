<?php

$services = db()->query("SELECT nome, categoria, duracao_minutos, preco FROM servicos WHERE ativo = 1 ORDER BY categoria, nome")->fetchAll();
?>
<section class="welcome-row"><div><p class="eyebrow">TABELA</p><h1>Preços</h1><p class="muted">Valores atuais dos serviços de <?= e($clinicName) ?>.</p></div></section>
<section class="panel">
    <div class="table-responsive">
        <table class="table clients-table">
            <thead><tr><th>Serviço</th><th>Categoria</th><th>Duração</th><th>Preço</th></tr></thead>
            <tbody>
            <?php foreach ($services as $service): ?>
                <tr>
                    <td><strong><?= e($service['nome']) ?></strong></td>
                    <td><?= $service['categoria'] === 'salao' ? 'Salão' : 'Podologia' ?></td>
                    <td><?= (int) $service['duracao_minutos'] ?> min</td>
                    <td><?= e(brl($service['preco'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
