<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = 'SPG Portal — მომხმარებლის ავტორიზაცია';
include __DIR__ . '/header.php';
?>
<section class="section" id="dashboard" style="scroll-margin-top:120px;">
  <div class="container" style="max-width:1000px;padding:34px 0 48px;display:grid;gap:16px;">
    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px;box-shadow:0 14px 32px rgba(15,23,42,.06)">
      <h2 style="margin:0 0 8px">მომხმარებლის Dashboard</h2>
      <p style="margin:0;color:var(--muted)">/user-auth.php#dashboard ბმული ახლა პირდაპირ ამ ბლოკზე გადმოგიყვანს.</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
        <a href="<?=h(url('news.php'))?>" class="btn">სიახლეები</a>
        <a href="<?=h(url('membership.php'))?>" class="btn primary">გაწევრიანების ფორმა</a>
      </div>
    </div>

    <div style="display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));">
      <div style="background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;">
        <h3 style="margin-bottom:12px">მომხმარებლის შესვლა</h3>
        <p style="color:var(--muted);margin-bottom:14px">ეს ფორმა განკუთვნილია მხოლოდ მომხმარებლისთვის და არა ადმინისტრატორისთვის.</p>
        <form action="#" method="post" style="display:grid;gap:12px">
          <div>
            <label for="signin-email">ელ-ფოსტა</label><br>
            <input id="signin-email" type="email" name="email" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
          </div>
          <div>
            <label for="signin-pass">პაროლი</label><br>
            <input id="signin-pass" type="password" name="password" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
          </div>
          <button type="submit" style="padding:12px 14px;border-radius:12px;border:0;background:#2563eb;color:#fff;font-weight:800;cursor:pointer">შესვლა</button>
        </form>
      </div>

      <div style="background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;">
        <h3 style="margin-bottom:12px">მომხმარებლის რეგისტრაცია</h3>
        <p style="color:var(--muted);margin-bottom:14px">შექმენი მომხმარებლის ახალი ანგარიში.</p>
        <form action="#" method="post" style="display:grid;gap:12px">
          <div>
            <label for="signup-name">სახელი და გვარი</label><br>
            <input id="signup-name" name="full_name" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
          </div>
          <div>
            <label for="signup-email">ელ-ფოსტა</label><br>
            <input id="signup-email" type="email" name="email" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
          </div>
          <div>
            <label for="signup-pass">პაროლი</label><br>
            <input id="signup-pass" type="password" name="password" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
          </div>
          <button type="submit" style="padding:12px 14px;border-radius:12px;border:0;background:#0ea5e9;color:#fff;font-weight:800;cursor:pointer">რეგისტრაცია</button>
        </form>
      </div>
    </div>
  </div>
</section>
<script>
  (function(){
    if (!window.location.hash) {
      history.replaceState(null, '', window.location.pathname + window.location.search + '#dashboard');
    }
    if (window.location.hash === '#dashboard') {
      document.getElementById('dashboard')?.scrollIntoView({behavior:'smooth', block:'start'});
    }
  })();
</script>
<?php include __DIR__ . '/footer.php'; ?>
