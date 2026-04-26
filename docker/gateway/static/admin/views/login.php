<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Admin — MGO2</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #0d0d0d; color: #e0e0e0; font-family: monospace;
       display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.box { background: #1a1a1a; border: 1px solid #333; padding: 2rem; width: 340px; }
h1 { font-size: 1rem; color: #c80; margin-bottom: 1.5rem; letter-spacing: 2px; text-transform: uppercase; }
label { display: block; font-size: .75rem; color: #888; margin-bottom: .3rem; }
input { width: 100%; padding: .6rem; background: #111; border: 1px solid #333;
        color: #e0e0e0; font-family: monospace; margin-bottom: 1rem; }
input:focus { outline: none; border-color: #c80; }
button { width: 100%; padding: .7rem; background: #c80; color: #000; border: none;
         font-family: monospace; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
button:hover { background: #a60; color: #fff; }
.error { color: #f66; font-size: .8rem; margin-bottom: 1rem; }
.brand { text-align: center; color: #444; font-size: .7rem; margin-top: 1.5rem; }
</style></head>
<body>
<div class="box">
  <h1>// MGO2 Admin</h1>
  <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <label>Username</label><input type="text" name="user" autofocus>
    <label>Password</label><input type="password" name="pass">
    <button type="submit">Login</button>
  </form>
  <div class="brand">NetworkMemories · Metal Gear Online 2</div>
</div>
</body></html>
