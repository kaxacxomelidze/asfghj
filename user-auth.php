<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = 'SPG Portal — მომხმარებლის ავტორიზაცია';
include __DIR__ . '/header.php';
?>
<section class="section" id="dashboard">
  <div class="container" style="max-width:900px;padding:40px 0;display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
    <div style="background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;">
      <h2 style="margin-bottom:12px">მომხმარებლის dashboard — შესვლა</h2>
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
      <h2 style="margin-bottom:12px">მომხმარებლის რეგისტრაცია</h2>
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
</section>
<?php include __DIR__ . '/footer.php'; ?>
