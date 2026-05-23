<?php
/** @var \App\View\AppView $this */
/** @var iterable<\App\Model\Entity\Release> $releases */
$colors = [
    'draft' => '#9e9e9e', 'building' => '#42a5f5', 'testing' => '#42a5f5',
    'evaluating' => '#7e57c2', 'manual_review' => '#ffb300', 'staging' => '#26a69a',
    'canary' => '#26a69a', 'production' => '#26a69a', 'released' => '#43a047', 'rejected' => '#e53935',
];
?>
<div style="max-width:880px;margin:2rem auto;font-family:system-ui,sans-serif;">
    <h1 style="margin-bottom:.25rem;">🚀 Release Pipeline</h1>
    <p style="color:#666;margin-top:0;">A software-release state machine powered by <code>dereuromark/cakephp-workflow</code>.</p>

    <?= $this->Flash->render() ?>

    <?= $this->Form->create(null, ['url' => ['action' => 'add'], 'style' => 'margin:1.5rem 0;display:flex;gap:.5rem;']) ?>
        <?= $this->Form->control('version', ['label' => false, 'placeholder' => 'Version (e.g. v2.4.0) — blank to auto-generate', 'style' => 'flex:1;padding:.6rem;border:1px solid #ccc;border-radius:6px;']) ?>
        <?= $this->Form->button('Start a new release ▶', ['style' => 'padding:.6rem 1.2rem;background:#1976d2;color:#fff;border:0;border-radius:6px;cursor:pointer;font-weight:600;']) ?>
    <?= $this->Form->end() ?>

    <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #eee;border-radius:8px;overflow:hidden;">
        <thead><tr style="background:#fafafa;text-align:left;">
            <th style="padding:.6rem 1rem;">#</th><th style="padding:.6rem 1rem;">Version</th>
            <th style="padding:.6rem 1rem;">State</th><th style="padding:.6rem 1rem;">Checks</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($releases as $r): $c = $colors[$r->state] ?? '#777'; ?>
            <tr style="border-top:1px solid #f0f0f0;">
                <td style="padding:.6rem 1rem;color:#999;">#<?= $r->id ?></td>
                <td style="padding:.6rem 1rem;font-weight:600;"><?= h($r->version) ?></td>
                <td style="padding:.6rem 1rem;"><span style="background:<?= $c ?>;color:#fff;padding:.2rem .6rem;border-radius:999px;font-size:.85rem;"><?= h($r->state) ?></span></td>
                <td style="padding:.6rem 1rem;color:#666;"><?= $r->check_attempts ?></td>
                <td style="padding:.6rem 1rem;"><?= $this->Html->link('Open →', ['action' => 'view', $r->id], ['style' => 'color:#1976d2;font-weight:600;text-decoration:none;']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$releases->count()): ?>
            <tr><td colspan="5" style="padding:1.5rem;text-align:center;color:#999;">No releases yet — start one above.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
