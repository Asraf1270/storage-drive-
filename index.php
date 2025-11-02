<?php
require_once 'config.php';

// Helper functions
function safe_path($rel) {
    // Prevent directory traversal and normalize slashes
    $rel = trim($rel);
    $rel = str_replace(['..', "\0"], '', $rel);
    $rel = trim($rel, '/\\');
    return $rel;
}

function abs_path($rel) {
    $safe = safe_path($rel);
    $abs = rtrim(STORAGE_ROOT, '/\\') . ($safe === '' ? '' : DIRECTORY_SEPARATOR . $safe);
    return $abs;
}

function relative_url($rel) {
    $safe = safe_path($rel);
    return urlencode($safe);
}

$current = isset($_GET['path']) ? safe_path($_GET['path']) : '';
$abs_current = abs_path($current);

// ensure folder exists
if (!is_dir($abs_current)) {
    // fallback to root
    $current = '';
    $abs_current = abs_path('');
}

// build breadcrumbs
$crumbs = [];
if ($current === '') $crumbs[] = ['name'=>'Home','path'=>''];
else {
    $parts = explode('/', $current);
    $acc = '';
    $crumbs[] = ['name'=>'Home','path'=>''];
    foreach ($parts as $p) {
        $acc = ($acc === '') ? $p : $acc . '/' . $p;
        $crumbs[] = ['name'=>$p,'path'=>$acc];
    }
}

// read directory
$items = scandir($abs_current);
$folders = $files = [];
foreach ($items as $it) {
    if ($it === '.' || $it === '..') continue;
    $full = $abs_current . DIRECTORY_SEPARATOR . $it;
    if (is_dir($full)) $folders[] = $it;
    else $files[] = $it;
}

$clipboard = isset($_SESSION['clipboard']) ? $_SESSION['clipboard'] : null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Storage — <?php echo $current===''?'Home':htmlspecialchars($current); ?></title>
<link rel="stylesheet" href="style.css" />
</head>
<body>
<header class="topbar">
  <!-- Logo / Title -->
  <div class="topbar-brand">
    <h1><span class="icon">Storage</span></h1>
  </div>

  <!-- Controls -->
  <div class="topbar-actions">
    <!-- Create Folder -->
    <form action="action.php" method="post" class="inline-form">
      <input type="hidden" name="action" value="create_folder">
      <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current); ?>">
      <div class="input-group">
        <input type="text" name="folder_name" placeholder="New folder" required>
        <button type="submit" class="btn-primary">Create</button>
      </div>
    </form>

    <!-- Upload Files -->
    <form id="uploadForm" action="action.php" method="post" enctype="multipart/form-data" class="inline">
      <input type="hidden" name="action" value="upload">
      <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current); ?>">
      <input type="file" name="files[]" multiple>
      <button type="submit">Upload</button>
    </form>

    <!-- Action Buttons -->
    <div class="btn-group">
      <button id="copyBtn" class="op-btn" data-op="copy">Copy</button>
      <button id="cutBtn" class="op-btn" data-op="cut">Cut</button>
      <form action="action.php" method="post" class="inline-form" id="pasteForm">
        <input type="hidden" name="action" value="paste">
        <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current); ?>">
        <button type="submit" id="pasteBtn" class="btn-secondary">Paste</button>
      </form>
      <button id="deleteBtn" class="btn-danger">Delete</button>
    </div>

    <!-- User Menu -->
    <div class="user-menu">
      <button id="themeToggle" class="icon-btn" title="Toggle dark mode">
        Dark Mode
      </button>
      <a href="logout.php" class="btn-logout">Logout</a>
    </div>
  </div>
</header>

<nav class="breadcrumbs">
    <?php foreach($crumbs as $c): ?>
      <a href="?path=<?php echo relative_url($c['path']); ?>"><?php echo htmlspecialchars($c['name']); ?></a>
      <?php if ($c !== end($crumbs)) echo ' / '; ?>
    <?php endforeach; ?>
  </nav>

<main class="container">
  

  <section class="listing">
    <form id="itemsForm" action="action.php" method="post">
      <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current); ?>">
      <table class="file-table" role="grid">
        <thead>
          <tr>
            <th><input id="selectAll" type="checkbox"></th>
            <th>Name</th>
            <th>Type</th>
            <th>Size</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($folders as $f): 
            $rel = ($current === '') ? $f : $current . '/' . $f;
          ?>
          <tr class="row folder">
            <td><input type="checkbox" name="items[]" value="<?php echo htmlspecialchars($rel); ?>"></td>
            <td class="name"><a class="folder-link" href="?path=<?php echo relative_url($rel); ?>"><?php echo htmlspecialchars($f); ?></a></td>
            <td>Folder</td>
            <td>-</td>
            <td>
              <button type="button" class="renameBtn" data-name="<?php echo htmlspecialchars($rel); ?>">Rename</button>
              <button type="button" class="downloadDisabled" title="Folders not downloadable">Download</button>
            </td>
          </tr>
          <?php endforeach; ?>

          <?php foreach ($files as $fi):
            $rel = ($current === '') ? $fi : $current . '/' . $fi;
            $full = $abs_current . DIRECTORY_SEPARATOR . $fi;
          ?>
          <tr class="row file">
            <td><input type="checkbox" name="items[]" value="<?php echo htmlspecialchars($rel); ?>"></td>
            <td class="name">
              <a href="https://storage.gamer.gd/storage/<?php echo htmlspecialchars($rel); ?>" target="_blank">
                <?php echo htmlspecialchars($fi); ?>
              </a>
            </td>
            <td><?php echo pathinfo($fi, PATHINFO_EXTENSION); ?></td>
            <td><?php echo filesize($full) >= 1024 ? number_format(filesize($full)/1024,2) . ' KB' : filesize($full) . ' B'; ?></td>
            <td>
              <button type="button" class="renameBtn" data-name="<?php echo htmlspecialchars($rel); ?>">Rename</button>
              <a class="downloadBtn" href="download.php?file=<?php echo relative_url($rel); ?>" download>Download</a>
            </td>
          </tr>
          <?php endforeach; ?>

          <?php if (empty($folders) && empty($files)): ?>
          <tr><td colspan="5" class="empty">This folder is empty.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <input type="hidden" name="action" id="actionField" value="">
    </form>
  </section>

  <aside class="sidebar">
    <h3>Clipboard</h3>
    <?php if ($clipboard): ?>
      <p><strong><?php echo htmlspecialchars($clipboard['op']); ?></strong></p>
      <ul>
        <?php foreach ($clipboard['items'] as $it): ?>
          <li><?php echo htmlspecialchars($it); ?></li>
        <?php endforeach; ?>
      </ul>
      <form action="action.php" method="post">
        <input type="hidden" name="action" value="clear_clipboard">
        <button type="submit">Clear</button>
      </form>
    <?php else: ?>
      <p>Clipboard is empty</p>
    <?php endif; ?>
  </aside>
</main>

<!-- rename modal (simple) -->
<div id="renameModal" class="modal" aria-hidden="true">
  <div class="modal-content">
    <h3>Rename</h3>
    <form id="renameForm" action="action.php" method="post">
      <input type="hidden" name="action" value="rename">
      <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current); ?>">
      <input type="hidden" name="item" id="renameItem">
      <input name="new_name" placeholder="New name" required>
      <div class="modal-actions">
        <button type="submit">Rename</button>
        <button type="button" id="renameCancel">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
/* basic client behavior */
document.getElementById('selectAll').addEventListener('change', function(e){
  const checked = e.target.checked;
  document.querySelectorAll('input[name="items[]"]').forEach(c => c.checked = checked);
});

document.querySelectorAll('.op-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const op = btn.dataset.op;
    const checked = Array.from(document.querySelectorAll('input[name="items[]"]:checked')).map(n => n.value);
    if (checked.length === 0) { alert('Select at least one item'); return; }
    // submit clipboard action
    const form = document.createElement('form');
    form.method = 'post';
    form.action = 'action.php';
    form.style.display = 'none';
    form.innerHTML = '<input type="hidden" name="action" value="'+op+'"><input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current); ?>">'
    checked.forEach(i => {
      const ip = document.createElement('input');
      ip.type = 'hidden'; ip.name = 'items[]'; ip.value = i;
      form.appendChild(ip);
    });
    document.body.appendChild(form);
    form.submit();
  });
});

document.getElementById('deleteBtn').addEventListener('click', () => {
  const checked = Array.from(document.querySelectorAll('input[name="items[]"]:checked')).map(n => n.value);
  if (checked.length === 0) { alert('Select items to delete'); return; }
  if (!confirm('Delete selected items? This cannot be undone.')) return;
  const f = document.createElement('form');
  f.method='post'; f.action='action.php';
  f.style.display='none';
  f.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current); ?>">';
  checked.forEach(i => { const ip=document.createElement('input'); ip.type='hidden'; ip.name='items[]'; ip.value=i; f.appendChild(ip);});
  document.body.appendChild(f); f.submit();
});

// rename modal flow
document.querySelectorAll('.renameBtn').forEach(b=>{
  b.addEventListener('click', () => {
    const item = b.dataset.name;
    document.getElementById('renameItem').value = item;
    document.getElementById('renameModal').style.display = 'block';
    document.getElementById('renameModal').setAttribute('aria-hidden','false');
  });
});
document.getElementById('renameCancel').addEventListener('click', () => {
  document.getElementById('renameModal').style.display = 'none';
  document.getElementById('renameModal').setAttribute('aria-hidden','true');
});
</script>
<script>
  const themeBtn = document.getElementById('themeToggle');
  const body = document.body;

  // Load saved theme
  if (localStorage.getItem('darkMode') === 'true') {
    body.classList.add('dark');
    themeBtn.textContent = 'Light Mode';
  }

  themeBtn.addEventListener('click', () => {
    body.classList.toggle('dark');
    const isDark = body.classList.contains('dark');
    themeBtn.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    localStorage.setItem('darkMode', isDark);
  });
</script>
</body>
</html>
