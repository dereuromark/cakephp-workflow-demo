<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\Release $release */
/** @var \Workflow\Engine\Definition\Definition $definition */
/** @var \Workflow\Engine\Definition\State $stateObj */
/** @var array<string> $manual */
/** @var bool $isFinal */
/** @var bool $isAuto */
/** @var array<\Workflow\Model\Entity\WorkflowTransition> $history */

$this->loadHelper('Workflow.Workflow');
$renderer = new \Workflow\Renderer\MermaidRenderer();
$color = $this->Workflow->getStateColor($definition, $release->state);
$label = $stateObj->getDisplayName();

$statusText = [
    'building' => 'Compiling and packaging the build…',
    'testing' => 'Running automated tests + smoke checks…',
    'evaluating' => 'Evaluating check results…',
    'staging' => 'Deploying to staging…',
    'canary' => 'Watching the canary cohort…',
    'production' => 'Rolling out to production…',
][$release->state] ?? null;

$labels = [
    'submit' => '▶ Submit for build', 'retry_check' => '🔄 Re-trigger checks',
    'approve' => '✅ Approve manually', 'reject' => '⛔ Reject',
];
?>
<div style="max-width:1080px;margin:1.5rem auto;font-family:system-ui,sans-serif;">
    <p><?= $this->Html->link('← All releases', ['action' => 'index'], ['style' => 'color:#1976d2;text-decoration:none;']) ?></p>
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <h1 style="margin:.2rem 0;">Release <?= h($release->version) ?></h1>
        <span id="state-badge" style="background:<?= $color ?>;color:#fff;padding:.35rem .9rem;border-radius:999px;font-weight:700;font-size:1rem;"><?= h($label) ?></span>
        <?php if ($isAuto): ?>
            <span id="auto-indicator" style="color:#888;font-size:.9rem;">⏳ auto-advancing…</span>
        <?php endif; ?>
    </div>

    <?= $this->Flash->render() ?>

    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:1rem;">
        <!-- LEFT: status + actions -->
        <div style="flex:1;min-width:320px;">
            <?php if ($statusText): ?>
                <div style="background:#e3f2fd;border-left:4px solid #1976d2;padding:.8rem 1rem;border-radius:6px;margin-bottom:1rem;">
                    <strong>Automated:</strong> <?= h($statusText) ?>
                </div>
            <?php endif; ?>

            <?php if (in_array($release->state, ['testing', 'evaluating'], true)): ?>
                <div style="margin-bottom:1rem;">
                    <div style="font-size:.85rem;color:#666;margin-bottom:.3rem;">Automated check attempts (escalates to manual review after 3)</div>
                    <div style="display:flex;gap:.4rem;">
                        <?php for ($i = 1; $i <= 3; $i++): $done = $release->check_attempts >= $i; ?>
                            <div style="flex:1;height:10px;border-radius:5px;background:<?= $done ? '#ef5350' : '#e0e0e0' ?>;" title="attempt <?= $i ?>"></div>
                        <?php endfor; ?>
                    </div>
                    <div style="font-size:.8rem;color:#999;margin-top:.3rem;"><?= (int)$release->check_attempts ?> / 3 failed</div>
                </div>
            <?php endif; ?>

            <?php if ($release->state === 'manual_review'): ?>
                <div style="background:#fff8e1;border-left:4px solid #ffb300;padding:.8rem 1rem;border-radius:6px;margin-bottom:1rem;">
                    <strong>Needs a human.</strong> <?= h($release->notes ?: 'Manual review required.') ?><br>
                    Re-trigger the checks (simulating a pushed fix), approve the rollout, or reject it.
                </div>
            <?php endif; ?>

            <?php if ($manual): ?>
                <div style="display:flex;flex-direction:column;gap:.6rem;max-width:340px;">
                    <?php foreach ($manual as $name): ?>
                        <?php if ($name === 'reject'): ?>
                            <?= $this->Form->create(null, ['url' => ['action' => 'transition', $release->id], 'style' => 'display:flex;gap:.4rem;']) ?>
                                <?= $this->Form->hidden('transition', ['value' => 'reject']) ?>
                                <?= $this->Form->control('reason', ['label' => false, 'required' => true, 'placeholder' => 'Reason (required)', 'style' => 'flex:1;padding:.5rem;border:1px solid #ccc;border-radius:6px;']) ?>
                                <?= $this->Form->button('⛔ Reject', ['style' => 'padding:.55rem 1rem;background:#e53935;color:#fff;border:0;border-radius:6px;cursor:pointer;font-weight:600;white-space:nowrap;']) ?>
                            <?= $this->Form->end() ?>
                        <?php else: ?>
                            <?php
                            $bg = $name === 'submit' || $name === 'approve' ? '#43a047' : '#1976d2';
                            echo $this->Form->postButton($labels[$name] ?? $name, ['action' => 'transition', $release->id], [
                                'data' => ['transition' => $name],
                                'style' => "padding:.6rem 1rem;background:$bg;color:#fff;border:0;border-radius:6px;cursor:pointer;font-weight:600;text-align:left;",
                            ]);
                            ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($isFinal): ?>
                <div style="background:<?= $release->state === 'released' ? '#e8f5e9' : '#ffebee' ?>;padding:1rem;border-radius:8px;font-weight:600;">
                    <?= $release->state === 'released' ? '🎉 Released to production.' : '🛑 Release rejected.' ?>
                </div>
            <?php endif; ?>

            <?php if ($release->notes): ?>
                <p style="margin-top:1rem;color:#555;font-style:italic;">“<?= h($release->notes) ?>”</p>
            <?php endif; ?>
        </div>

        <!-- RIGHT: live workflow diagram -->
        <div style="flex:1;min-width:380px;">
            <div style="font-size:.8rem;color:#999;margin-bottom:.3rem;">Workflow (━ manual · ┄ automatic · green = happy path · amber = current) — 🛡️ guard · ⚙️ command · ❓ condition</div>
            <div class="mermaid" style="background:#fff;border:1px solid #eee;border-radius:8px;padding:1rem;"><?= $renderer->render($definition, $release->state, true) ?></div>
        </div>
    </div>

    <!-- HISTORY -->
    <h3 style="margin-top:2rem;">Transition history</h3>
    <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #eee;border-radius:8px;overflow:hidden;font-size:.9rem;">
        <thead><tr style="background:#fafafa;text-align:left;">
            <th style="padding:.5rem .8rem;">When</th><th style="padding:.5rem .8rem;">Transition</th>
            <th style="padding:.5rem .8rem;">From → To</th><th style="padding:.5rem .8rem;">Status</th>
            <th style="padding:.5rem .8rem;">By</th><th style="padding:.5rem .8rem;">Detail (blocked reason / error)</th>
        </tr></thead>
        <tbody>
        <?php foreach ($history as $h):
            $sc = ['success' => '#43a047', 'blocked' => '#ffb300', 'locked' => '#ff7043', 'error' => '#e53935'][$h->status] ?? '#777';
            $ctx = $h->context;
            if (!is_array($ctx)) {
                $ctx = $ctx ? (array)json_decode((string)$ctx, true) : [];
            }
            $by = $ctx['triggered_by'] ?? '';
            $detail = '';
            if (!empty($ctx['_blocked_by'])) {
                $detail = implode('; ', (array)$ctx['_blocked_by']);
            } elseif (!empty($ctx['_error']['message'])) {
                $detail = $ctx['_error']['message'] . ' (' . ($ctx['_error']['class'] ?? '') . ')';
            }
        ?>
            <tr style="border-top:1px solid #f0f0f0;">
                <td style="padding:.5rem .8rem;color:#999;white-space:nowrap;"><?= $h->created?->format('H:i:s') ?></td>
                <td style="padding:.5rem .8rem;font-weight:600;"><?= h($h->transition_name) ?></td>
                <td style="padding:.5rem .8rem;color:#666;"><?= h($h->from_state) ?> → <?= h($h->to_state) ?></td>
                <td style="padding:.5rem .8rem;"><span style="color:<?= $sc ?>;font-weight:600;"><?= h($h->status) ?></span></td>
                <td style="padding:.5rem .8rem;color:#999;"><?= h($by) ?></td>
                <td style="padding:.5rem .8rem;color:<?= $detail ? $sc : '#ccc' ?>;font-size:.85rem;"><?= h($detail) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$history): ?>
            <tr><td colspan="6" style="padding:1rem;text-align:center;color:#999;">No transitions yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->Workflow->includeMermaid() ?>
<?php if ($isAuto): ?>
<script>
(function () {
    const runUrl = <?= json_encode($this->Url->build(['action' => 'run', $release->id])) ?>;
    function poll() {
        fetch(runUrl, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(r => r.json())
            .then(d => {
                if (d.fired > 0) { window.location.reload(); return; }
                if (d.keepPolling) { setTimeout(poll, 1200); }
            })
            .catch(() => setTimeout(poll, 2000));
    }
    setTimeout(poll, 1200);
})();
</script>
<?php endif; ?>
