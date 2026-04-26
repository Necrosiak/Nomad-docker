<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Admin — MGO2 Nomad</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #0d0d0d; color: #e0e0e0; font-family: monospace; }
header { background: #1a1a1a; border-bottom: 1px solid #333; padding: 1rem 2rem;
         display: flex; justify-content: space-between; align-items: center; }
header h1 { color: #c80; font-size: .9rem; letter-spacing: 2px; text-transform: uppercase; }
header form button { background: none; border: 1px solid #444; color: #888;
                     padding: .3rem .8rem; cursor: pointer; font-family: monospace; font-size: .75rem; }
header form button:hover { border-color: #c80; color: #c80; }
main { padding: 2rem; max-width: 1100px; }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.card { background: #1a1a1a; border: 1px solid #333; padding: 1.2rem; }
.card .label { font-size: .7rem; color: #666; text-transform: uppercase; letter-spacing: 1px; }
.card .value { font-size: 2rem; margin-top: .4rem; }
.value.ok { color: #4c4; } .value.warn { color: #c80; } .value.bad { color: #c44; }
.banner { background: #1a1000; border-left: 3px solid #c80; padding: .8rem 1rem;
          margin-bottom: 2rem; font-size: .82rem; line-height: 1.6; }
.banner strong { color: #c80; }
.banner a { color: #c80; }
section { margin-bottom: 2rem; }
section h2 { font-size: .8rem; color: #666; text-transform: uppercase; letter-spacing: 1px;
             border-bottom: 1px solid #222; padding-bottom: .5rem; margin-bottom: 1rem; }
table { width: 100%; border-collapse: collapse; font-size: .82rem; }
th { text-align: left; color: #666; font-weight: normal; padding: .4rem .6rem;
     border-bottom: 1px solid #222; font-size: .7rem; text-transform: uppercase; }
td { padding: .5rem .6rem; border-bottom: 1px solid #1a1a1a; }
tr:hover td { background: #1a1a1a; }
.tag { display: inline-block; padding: .1rem .4rem; font-size: .7rem; border: 1px solid #333; }
.tag.online { border-color: #4c4; color: #4c4; }
.tag.banned { border-color: #c44; color: #c44; }
.btn { display: inline-block; padding: .2rem .6rem; font-family: monospace; font-size: .72rem;
       cursor: pointer; border: 1px solid #444; background: none; color: #aaa; }
.btn:hover { border-color: #c80; color: #c80; }
.btn-danger { border-color: #633; color: #c44; }
.btn-danger:hover { border-color: #c00; color: #c00; }
.alert-ok { padding: .6rem 1rem; background: #001a00; border-left: 3px solid #4c4;
            margin-bottom: 1rem; font-size: .8rem; }
</style></head>
<body>
<header>
  <h1>// NetworkMemories · MGO2 Admin</h1>
  <form method="POST"><button name="logout" value="1">Logout</button></form>
</header>
<main>

<!-- RPCS3 status banner — always visible -->
<div class="banner">
  <strong>🚧 RPCS3 Client Redirect — Work in Progress</strong><br>
  DNS redirection works for <strong>real PS3 hardware</strong> only.
  RPCS3 requires a patched <code>plugin.sprx</code> — the SaveMGO plugin hardcodes
  its server address and ignores DNS. Recompilation of the plugin is currently in progress.
  <a href="/docs/rpcs3-plugin-blocker.md" target="_blank">→ Full details & current status</a>
</div>

<?php if (!empty($message)): ?>
<div class="alert-ok"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if (!empty($db_error)): ?>
<div style="padding:.6rem 1rem;background:#1a0000;border-left:3px solid #c00;margin-bottom:1rem;font-size:.8rem">
  DB error: <?= htmlspecialchars($db_error) ?>
</div>
<?php endif; ?>

<div class="grid">
  <div class="card">
    <div class="label">Accounts</div>
    <div class="value"><?= htmlspecialchars($stats['accounts'] ?? '—') ?></div>
  </div>
  <div class="card">
    <div class="label">Active Sessions</div>
    <div class="value ok"><?= htmlspecialchars($stats['sessions'] ?? '—') ?></div>
  </div>
  <div class="card">
    <div class="label">Nomad Server</div>
    <?php
      $ports = [10020, 10100, 10200, 10201];
      $alive = false;
      foreach ($ports as $p) {
        $s = @fsockopen($server_ip, $p, $e, $err, 1);
        if ($s) { fclose($s); $alive = true; break; }
      }
    ?>
    <div class="value <?= $alive ? 'ok' : 'bad' ?>"><?= $alive ? 'Online' : 'Offline' ?></div>
  </div>
  <div class="card">
    <div class="label">RPCS3 Plugin</div>
    <div class="value warn">WIP</div>
  </div>
</div>

<section>
  <h2>Players</h2>
  <table>
    <thead><tr>
      <th>ID</th><th>Username</th><th>Status</th>
      <th>Created</th><th>Last Login</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach ($players as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><?= htmlspecialchars($p['username']) ?></td>
      <td>
        <?php if ($p['banned']): ?>
          <span class="tag banned">Banned</span>
        <?php elseif ($p['online']): ?>
          <span class="tag online">Online</span>
        <?php else: ?>
          —
        <?php endif; ?>
      </td>
      <td><?= htmlspecialchars($p['created_at'] ?? '—') ?></td>
      <td><?= htmlspecialchars($p['last_login'] ?? '—') ?></td>
      <td>
        <form method="POST" style="display:inline">
          <input type="hidden" name="account_id" value="<?= $p['id'] ?>">
          <button name="action" value="kick" class="btn">Kick</button>
          <?php if ($p['banned']): ?>
            <button name="action" value="unban" class="btn">Unban</button>
          <?php else: ?>
            <button name="action" value="ban" class="btn btn-danger"
                    onclick="return confirm('Ban?')">Ban</button>
          <?php endif; ?>
          <button name="action" value="delete" class="btn btn-danger"
                  onclick="return confirm('Delete permanently?')">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($players)): ?>
    <tr><td colspan="6" style="color:#555">No accounts yet</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</section>

<section>
  <h2>Server Info</h2>
  <table>
    <tr><th>Server IP</th><td><?= htmlspecialchars($server_ip) ?></td></tr>
    <tr><th>Ports</th><td>10020 / 10100 / 10200 / 10201</td></tr>
    <tr><th>Database</th><td><?= htmlspecialchars($db_name) ?></td></tr>
    <tr><th>PHP</th><td><?= phpversion() ?></td></tr>
    <tr><th>RPCS3 status</th>
        <td>🚧 plugin.sprx recompilation in progress —
            <a href="/docs/rpcs3-plugin-blocker.md" style="color:#c80">see docs</a></td></tr>
  </table>
</section>

</main>
</body></html>
